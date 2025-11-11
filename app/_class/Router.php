<?php

use Pecee\SimpleRouter\SimpleRouter;

$routeList = [];
$routeFiles = glob(APP_DIR . '/routes/*.php');
foreach ($routeFiles as $routeFilePath) {
    $routeFile = basename($routeFilePath);
    $inc = @include_once(APP_DIR . '/routes/' . $routeFile);
    if (is_array($inc)) {
        $routeList[$routeFile] = $inc;
    }
}

foreach ($routeList as $module => $routes) {
    foreach ($routes as $name => $params) {
        extract($params);
        if(!is_array($via)) $via = [$via];
        foreach($via as $i => $method){
            $via[$i] = strtolower($method);
        }

        if(!isset($conditions)) $conditions = [];
        SimpleRouter::match($via, $pattern, function() use ($callable, $type) {
            list($dir, $file) = $callable;
            define('DIRECTORY', $dir);

            if($type == 'ajax'){
                define('AJAX_ACTION', $file);
                define('FILE', '_i.php');
            } else {
                define('FILE', $file);                
            }

            $fileExists = @chdir(APP_DIR . '/' . DIRECTORY);
            if($fileExists) require_once(FILE);
        })->where($conditions)->name($name);
    }
}
