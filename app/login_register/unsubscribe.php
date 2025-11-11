<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['email_unsubscribe' => '/login_register/tpl/email_unsubscribe.html']);
$tpl->split_template('email_unsubscribe', 'EMAIL_UNSUBSCRIBE');

$tpl->assign("EMAIL", $email);

$tpl->parse("CONTENT", "email_unsubscribe");
include_once('../_body.php');