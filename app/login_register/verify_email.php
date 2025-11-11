<?php

include_once('../_main.php');
include_once('./_config.php');

$result = $db->query("SELECT * FROM tbl_user WHERE VALIDATION_CODE = ?", $code)->fetchArray();
if(!$result){
    $response->redirect($tpl->urlFor('index'));
    die;
}

$user->deleteStatus($result['UID'], 3);

$tpl->define(['verify_email' => '/login_register/tpl/verify_email.html']);
$tpl->split_template('verify_email', 'VERIFY_EMAIL');

$tpl->parse("CONTENT", "verify_email");
include_once('../_body.php');