<?php

define('ROOT_DIR', realpath(dirname(__FILE__) . '/..'));

require_once ROOT_DIR . '/vendor/autoload.php';

spl_autoload_register(function ($class) {
    $dirs = explode('\\', $class);

    $filename = array_pop($dirs) . '.php';

    $path = ROOT_DIR . '/src';
    foreach ($dirs as $dir) {
        $path .= '/' . $dir;
        if ( !is_dir($path) ) {
            throw new Exception("$path not found");
        }
    }

    $path .= '/' . $filename;
    if ( !file_exists($path) ) {
        throw new Exception("$path not found");
    }

    require_once $path;
});
