<?php

if(!$user->isOnline){    
    $tpl->define([
        'login_page' => '/home/tpl/login_page.html',
        'access' => '/products/tpl/_access.html',
    ]);
    $tpl->split_template('login_page', 'LOGIN_PAGE');

    $tpl->parse("CONTENT", ".access");
    $tpl->parse("CONTENT", ".login_page");
    include_once('../_body.php');
}