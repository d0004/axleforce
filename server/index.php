<?php 

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// if($_SERVER['REQUEST_URI'] == '/lv/'){
//     header("HTTP/1.1 301 Moved Permanently"); 
//     header("Location: /"); 
//     die;
// }

define('APP_START', true);
define('APP_DIR', '/home/admin/web/axleforce.lv/public_html/app');
define('PUBLIC_DIR', '/home/admin/web/axleforce.lv/public_html/server');
define('ROOT_DIR', '/home/admin/web/axleforce.lv/public_html');

require_once(APP_DIR . '/SplClassLoader.php');
$loader = new SplClassLoader(null, APP_DIR);
$loader->register();

require_once(ROOT_DIR . '/vendor/autoload.php');

use Pecee\SimpleRouter\SimpleRouter;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

require_once APP_DIR . '/_class/RouteHelpers.php';
require_once APP_DIR . '/_class/Router.php';

SimpleRouter::setDefaultNamespace('');
try{
    SimpleRouter::start();
} catch(NotFoundHttpException $e){
    $fileExists = false;
    include_once(APP_DIR . '/_main.php'); 
    die;
}