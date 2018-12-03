<?php

spl_autoload_register(function($classname) {
    if(preg_match('/^\\\\?Kami/', $classname)) {
        $classname = preg_replace('/^\\\\?Kami/', '', $classname);
        $filename = preg_replace('/\\\\/', '/', $classname) .'.php';
        $full_path = __DIR__ . $filename;
        if(file_exists($full_path)) {
            include_once($full_path);
        }
    }
});