<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['reset_password' => '/login_register/tpl/reset_password.html']);
$tpl->split_template('reset_password', 'RESET_PASSWORD');

$result = $db->query("SELECT * FROM password_recovery_requests WHERE CODE = ? AND STATUS = 0 AND EXPIRE_DATE > now()", $code)->fetchArray();
if(!$result){
    $response->redirect($tpl->urlFor('index'));
    die;
}

$tpl->assign("CODE", $code);

$tpl->parse("CONTENT", "reset_password");
include_once('../_body.php');