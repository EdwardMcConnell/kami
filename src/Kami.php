<?php
namespace Kami;

/**
 * Kami - Japanese translation of "paper"
 * 
 * Kami is a templating engine aimed at rapid application customizations with
 * common component libraries
 * 
 * There are a few paths you can take with Kami.
 * 
 * To output an entire page, use
 *  Kami::from($page_template_file_path)
 *      ->string();
 * 
 * Adding data is a breeze
 *  Kami::from($template_file_path)
 *      ->write($my_data)
 *      ->string();
 * 
 * Using components
 *  Kami::from($input_component_file)->registerComponent('input');
 *  Kami::component('input')->write($my_component_properties);
 * 
 * Globally registering a component on load
 *  Load the string '{{#input}}<input type="text" value="{{$value}}"{{/input}}'
 *  either through the ::from or ::component method then:
 *  Kami::component('input')->string()
 * 
 * Use a template as data
 *  Kami::from($my_stylish_page)
 *      ->write(
 *          Kami::component('input')
 *              ->write($my_input_properties)
 *              ->as('my_variable_name')
 *      )
 *      ->string();
 */
class Kami {
    protected static $cache = [];
    protected static $components = [];
    protected static $globals = [];
    protected $template;
    protected $data = [];

    public static function c($str) {
        return static::component($str);
    }

    public static function component($str) {
        $k = new self();
        if($str{0} != '{' && isset(static::$components[$str])) {
            $k->template = static::$components[$str];
        } else {
            $k->template = $k->compile($str);
        }
        return $k;
    }

    public static function from($template_file) {
        $k = new self();
        $k->template = $k->load($template_file);
        return $k;
    }

    public static function getComponents() {
        return static::$components;
    }

    public function load($file) {
        if(isset(static::$cache[$file])) {
            return static::$cache[$file];
        }

        if(!file_exists($file)) {
            throw new \Exception("Kami cannot find file $file");
        }

        $content = file_get_contents($file);

        return static::$cache[$file] = $this->compile($content);
    }

    public function compile($content) {
        $parts = preg_split('/\\{\\{|\\}\\}/', $content);
        $is_component = false;
        $component_name = '';
        $component = [];
        $parsed = [];
        foreach($parts as $part) {
            if($part{0} == '#') {
                $component_name = substr($part,1);
                $is_component = true;
                continue;
            }

            if($part == '/'. $component_name) {
                static::$components[$component_name] = $component;
                $parsed[] = '$_'. preg_replace('/[^a-zA-Z_0-9]/', '_', $component_name);
                $component = [];
                $component_name = '';
                $is_component = false;
                continue;
            }

            if(!$is_component) {
                $parsed[] = $part;
            } else {
                $component[] = $part;
            }
        }

        if($is_component) throw new \Exception('Unclosed component tag encountered: '. $component_name);
        return $parsed;
    }

    public function registerComponent($title) {
        static::$components[$title] = $this->template;
        return $this;
    }

    public function output() {
        $out = [];
        $iterations = empty($this->data['_iterative_data']) ? [[]] : $this->data['_iterative_data'];
        $_idx = 0;
        foreach($iterations as $iter_data) {
            $iteration_data = !is_array($iter_data) ? $this->data : array_merge($this->data, $iter_data);
            foreach($this->template as $part) {
                if($part == '$_idx') {
                    $out[] = $_idx;
                } else if(substr($part,0,2) == '__' && $part != '__') {
                    $component = substr($part,2);
                    if(isset(static::$components[$component])) {
                        $out[] = static::component($component)
                            ->write($iteration_data)
                            ->string();
                    }
                } else if($part{0} == '$') {
                    $k = substr($part,1);
                    $val = isset($iteration_data[$k]) ? $iteration_data[$k] : '';
                    if(is_array($val)) {
                        $out[] = implode('',$val);
                    } else {
                        $out[] = $val;
                    }
                } else {
                    $out[] = $part;
                }
            }
            $_idx++;
        }
        return $out;
    }

    public function string() {
        $out = $this->output();
        return implode("", $out);
    }

    public function print() {
        print $this->string();
    }

    public function as($var_name) {
        return [$var_name => $this->string()];
    }

    public function write($data) {
        foreach($data as $key=>$val) {
            if(is_numeric($key) && is_array($val)) {
                $this->data['_iterative_data'][] = $val;
            } else {
                $this->data[$key] = $val;
            }
        }
        return $this;
    }

}