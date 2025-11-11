<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['forgot_pass' => '/login_register/tpl/forgot_pass.html']);
$tpl->split_template('forgot_pass', 'FORGOT_PASS');



$tpl->parse("CONTENT", "forgot_pass");
include_once('../_body.php');