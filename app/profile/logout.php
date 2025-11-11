<?php

include_once('../_main.php');

if(!$user->isOnline){
    header("Location: " . $tpl->urlFor('index'));
    die;
}

$nextRoute = 'index';
if($_SESSION['LOGOUT_REDIRECT']){
    $nextRoute = $_SESSION['LOGOUT_REDIRECT'];
}

$user->logout();

header("Location: " . $tpl->urlFor($nextRoute));
die;