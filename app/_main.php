<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include_once(APP_DIR . '/config/config.php');
include_once(APP_DIR . '/config/db.php');

include_once(APP_DIR . '/config/params.php');
include_once(APP_DIR . '/libs/functions.php');

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

// require_once(APP_DIR . '/vendor/autoload.php');
 
session_set_cookie_params(PHP_INT_MAX);
session_start();

use \_class\Registry;
Registry::__init();

Registry::add('imageSizes', $imageSizes);

// Registry::add('router', $router);

$db = new \_class\db(DB_HOST, DB_USER, DB_PASS, DB_NAME);
Registry::add('db', $db);

$response = new \_class\response();
Registry::add('response', $response);

$lang = $defaultLang;
Registry::add('defaultLang', $defaultLang);
Registry::add('fullLanguages', $fullLanguages);

if(request()->getLoadedRoute() != null){
    extract(request()->getLoadedRoute()->getParameters());
} else {
    $fileExists = false;
}

if (isset($_COOKIE['sln']) && isset($fullLanguages[$_COOKIE['sln']])) {
	$lang = $_COOKIE['sln'];
}

if (isset($ln) && isset($fullLanguages[$ln])) {
	if ($lang != $ln) {
		$lang = $ln;
		setcookie('sln', $lang, time() + 8640000, '/');
	}
}

Registry::add('lang', $lang);

$wordProcessing = new \_class\WordsProcessing;
$wordProcessing->set_db($db);
$wordProcessing->lang = $lang;

$tpl = new \_class\FastTemplate(APP_DIR);
Registry::add('tpl', $tpl);

$tpl->setWordsProcessing($wordProcessing);

// $tpl->set_router($router);
$tpl->set_lang($lang);
$tpl->set_db($db);

$document = new \_class\Document;
Registry::add('document', $document);

$request = new \_class\Request($_POST, $_GET, $_FILES, $_SERVER, $_COOKIE, $_SESSION);
Registry::add('request', $request);

Registry::add('countries', $countries);


$tpl->define([
    'main' => '/system/tpl/main.html',
    'main_admin' => '/system/tpl/main_admin.html',
    'scripts' => '/system/tpl/scripts.html',
]);
$tpl->split_template('main', 'MAIN');
$tpl->split_template('main_admin', 'MAIN_ADMIN');
$tpl->split_template('scripts', 'SCRIPTS');

$tpl->parse("HTML_SCRIPTS", "scripts");

$tpl->assign("CRCH", "€");

$user = new \_class\User();
$user->prepare();
Registry::add('user', $user);

$money = new \_class\Money();
Registry::add('money', $money);

// if($request->get['hideCookieBlock'] == 1){
//     $tpl->assign('HIDE_COOKIE_BLOCK', true);
// }

$tpl->assign("IS_ONLINE", $user->isOnline);

if($user->isOnline){
    $tpl->assign("CONFIRM_REQUIRED", false);
    if($user->hasStatus(3) && !$_COOKIE['confirm_required']){
        setcookie("confirm_required", "1", time() + 60 * 5);
        $tpl->assign("CONFIRM_REQUIRED", true);
        $tpl->assign("EMAIL_ADDRESS", $user->userData['EMAIL']);
    }
}

$tpl->assign("UID", 0);
if(isset($user->uid)){
    $tpl->assign("UID", $user->uid);
}

if($user->hasStatus(40, $user->uid)){
    setcookie('alllang', 1);
}


$productCart = new \products\ProductCart;
$tpl->assign("CART_DROPDOWN_CONTENT", $productCart->getCartDropdown());
$tpl->assign("SMALL_CART_CONTENT", $productCart->getSmallCart());
$tpl->assign("CART_ITEM_COUNT", $productCart->itemCount);

// $menu = new \system\Menu();
// $menu->desktopCategoryMenu();
// $menu->mobileCategoryMenu();
// $menu->showMenu();

if(!$fileExists){
    $error = new \_class\Error;
    $error->show404();
    include_once(APP_DIR . '/_body.php');
    die;
}

if(defined('AJAX_ACTION')) $a = AJAX_ACTION;

$access = new \_class\Access($db, $user);
if(FILE == "_i.php"){
    if(strpos($request->server['HTTP_REFERER'], $request->server['SERVER_NAME']) == false){
        $response->setOutput('Server can\'t perform this action');
        $response->addHeader('HTTP/1.1 403 Forbidden');
        $response->output();
        die;
    }

    if(!$access->checkAccessI(DIRECTORY, $a)){
        echo 'Error 403! Permission denied :(';
        die;
    }
} else {
    if(!$access->checkAccessFile(DIRECTORY, FILE)){
        // var_dump('_access.php'); die;
        if(file_exists('_access.php')){
            include_once('_access.php');    
        }
        
        $tpl->define(["error_403" => "/system/tpl/403.html"]);
        $tpl->parse("CONTENT", "error_403");
        include_once('../_body.php');
        die;
    
    }
}

// $tpl->assign("CURRENT_ROUTE", $router->getCurrentRoute()->getName());
$tpl->assign("CURRENT_ROUTE", request()->getLoadedRoute()->getName());