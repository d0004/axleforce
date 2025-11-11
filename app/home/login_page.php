<?php

include_once('../_main.php');
include_once('./_config.php');

if($user->isOnline){
    $response->redirect($tpl->urlFor('index'));
    die;
}


$tpl->define(['login_page' => '/home/tpl/login_page.html']);
$tpl->split_template('login_page', 'LOGIN_PAGE');



$tpl->parse("CONTENT", "login_page");
include_once('../_body.php');