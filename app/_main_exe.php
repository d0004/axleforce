<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if(!defined('APP_DIR'))
    define('APP_DIR', '/home/admin/web/axleforce.lv/public_html/app');

if(!defined('PUBLIC_DIR'))
    define('PUBLIC_DIR', '/home/admin/web/axleforce.lv/public_html/server');

if(!defined('ROOT_DIR'))
    define('ROOT_DIR', '/home/admin/web/axleforce.lv/public_html');

include_once(APP_DIR . '/config/config.php');
include_once(APP_DIR . '/config/db.php');
include_once(APP_DIR . '/config/params.php');
include_once(APP_DIR . '/libs/functions.php');

require_once(ROOT_DIR . '/vendor/autoload.php');

require_once(APP_DIR . '/SplClassLoader.php');

$loader = new SplClassLoader(null, APP_DIR);
$loader->register();

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

session_start();

use \_class\Registry;
Registry::__init();

$db = new \_class\db(DB_HOST, DB_USER, DB_PASS, DB_NAME);
Registry::add('db', $db);

$response = new \_class\response();
Registry::add('response', $response);

$money = new \_class\Money();
Registry::add('money', $money);


$request = new \_class\Request($_POST, $_GET, $_FILES, $_SERVER, $_COOKIE, $_SESSION);
Registry::add('request', $request);

$tpl = new \_class\FastTemplate(APP_DIR);
Registry::add('tpl', $tpl);
$tpl->set_lang('lv');
$tpl->set_db($db);