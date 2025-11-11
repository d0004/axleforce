<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['privacy_policy' => "/home/tpl/privacy_policy_$lang.html"]);
$tpl->split_template('privacy_policy', 'PRIVACY_POLICY');

$tpl->parse("CONTENT", "privacy_policy");
include_once('../_body.php');