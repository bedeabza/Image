<?php
error_reporting(E_ALL^E_NOTICE);

$paths = array(
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src',
    get_include_path()
);

set_include_path(implode(PATH_SEPARATOR, $paths));
spl_autoload_register(function($className){
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strripos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    require $fileName;
});
