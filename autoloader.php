<?php

class ClassAutoloader {

    public function __construct() {
        spl_autoload_register(array($this, 'loader'));
    }

    private function loader($classPath) {

        $classArray = explode('\\', $classPath);
        $className = array_pop($classArray);

        $include_path = ROOT;

        foreach($classArray as $path_part) {
            $include_path .= '/' . strtolower($path_part) . '/';
        }
        $include_path .= $className . '.php';
        if (is_file($include_path)) {
            include_once $include_path;
        }
    }
}