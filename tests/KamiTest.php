<?php

use \PHPUnit\Framework\TestCase;
use \Kami\Kami;

class KamiTest extends TestCase {

    public function testInit() {
        Kami::from(__DIR__.'/templates/simplest.tmpl.html');
        $this->assertTrue(true);
    }

    public function testOutputString() {
        $k = Kami::component('{{$thing}}');
        $this->assertEquals('', $k->string());
    }

    public function testOutputStringWithContent() {
        $k = Kami::component('{{$thing}}');
        $this->assertEquals('hi', $k->write(['thing'=>'hi'])->string());
    }

    public function testOutputStringWithArray() {
        $k = Kami::component('{{$thing}}');
        $this->assertEquals('hihihi', $k->write(['thing'=>['hi','hi','hi']])->string());
    }

    public function testLoadingFileAndOutput() {
        $this->assertEquals('hi',
            Kami::from(__DIR__.'/templates/simplest.tmpl.html')
                ->write(['thing'=>'hi'])
                ->string()
        );
    }

    public function testSubComponents() {
        $this->assertEquals('hi, there',
            Kami::component('{{$var1}}, {{$var2}}')
                ->write(
                    Kami::component('{{$subvar1}}')
                        ->write(['subvar1'=>'there'])
                        ->as('var2')
                )
                ->write(['var1' => 'hi'])
                ->string()
        );
    }

    public function testRegisterComponents() {
        Kami::component('{{$var1}}')->registerComponent('testing');
        $this->assertArrayHasKey('testing', Kami::getComponents());
    }

    public function testAutoRegisterComponents() {
        Kami::component('{{#input}}<input type="text">{{/input}}');
        $this->assertArrayHasKey('input', Kami::getComponents());
    }

    public function testUsingAutoRegisteredComponents() {
        Kami::component('{{#input}}<input type="text" name="{{$name}}">{{/input}}');
        $this->assertEquals(
            '<input type="text" name="myInput">',
            Kami::component('input')
                ->write(['name'=>'myInput'])
                ->string()
        );
    }

    public function testUsingAutoRegisteredComponentsAsSub() {
        Kami::component('{{#input}}<input type="text" name="{{$name}}">{{/input}}');
        $this->assertEquals(
            '<input type="text" name="myInput">',
            Kami::component('{{$var}}')
                ->write(
                    Kami::component('input')
                        ->write(['name'=>'myInput'])
                        ->as('var')
                )
                ->string()
        );
    }

    public function testAutoRegComponentArray() {
        Kami::from(__DIR__.'/templates/input_group.tmpl.html');
        $expected_out = <<<END

<div class="input-group">
    <label for="test_0"></label>
    <input type="text" id="test_0" value="1">
</div>

<div class="input-group">
    <label for="test_1"></label>
    <input type="text" id="test_1" value="2">
</div>

<div class="input-group">
    <label for="test_2"></label>
    <input type="text" id="test_2" value="3">
</div>

END;
        $this->assertEquals($expected_out,
            Kami::component('input_group')
                ->write(['name'=>'test'])
                ->write([
                    ['val' => 1],
                    ['val' => 2],
                    ['val' => 3]
                ])
                ->string());
    }

    public function testComponentMultiUse() {
        Kami::component('{{#testing}}{{$val}}{{/testing}}');
        $str1 = Kami::component('testing')
                    ->write(['val'=>'hi'])
                    ->string();
        $str2 = Kami::component('testing')
                    ->write(['val'=>'there'])
                    ->string();
        $this->assertEquals('hi', $str1);
        $this->assertEquals('there', $str2);
    }

    public function testInvalidComponent(){
        try {
            Kami::component('{{#testing}}this isn\'t closed');
            $this->assertTrue(false); //Should not get here
        } catch (Exception $e) {
            $this->assertContains('Unclosed', $e->getMessage());
        }
    }

    public function testInheritance() {
        Kami::component('{{#t}}thing{{/t}}');
        $this->assertEquals(
            '<div>thing</div>',
            Kami::component('<div>{{__t}}</div>')->string()
        );
    }

    public function testInheritanceVars() {
        Kami::component('{{#t}}<span>{{$something}}</span>{{/t}}');
        $this->assertEquals(
            '<div><span>whatever</span></div>',
            Kami::component('<div>{{__t}}</div>')->write(['something'=>'whatever'])->string()
        );
    }

    public function testInheritanceChildren() {
        Kami::component('{{#spanlink}}<span>{{__a}}</span>{{/spanlink}}');
        Kami::component('{{#a}}<a href="{{$link}}">{{$text}}</a>{{/a}}');
        $this->assertEquals(
            '<div><span><a href="#">link text</a></span></div>',
            Kami::component('<div>{{__spanlink}}</div>')
                ->write(['link'=>'#','text'=>'link text'])
                ->string()
        );
    }

    public function testInheritanceContainer() {
        Kami::component('{{#spanlink}}<span>{{__a}}</span>{{/spanlink}}');
        Kami::component('{{#a}}<a href="{{$link}}">{{$text}}</a>{{/a}}');
        Kami::component('{{#linkgroup}}<div class="link-group">{{__spanlink}}</div>{{/linkgroup}}');
        Kami::component('{{#container}}<div class="container">{{$children}}</div>{{/container}}');
        $this->assertEquals(
            '<div class="container"><div class="link-group"><span><a href="#">link text</a></span></div></div>',
            Kami::component('container')->write(
                Kami::component('linkgroup')
                    ->write(['link'=>'#','text'=>'link text'])
                    ->as('children')
            )->string()
        );
    }

    public function testShorthand() {
        $this->assertEquals('this',Kami::c('this')->string());
    }
}