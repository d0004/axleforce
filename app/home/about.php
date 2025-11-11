<?php

include_once('../_main.php');
include_once('./_config.php');

$document->setMetaTitle("AxleForce | Par Mums");

$tpl->define(['about' => '/home/tpl/about.html']);
$tpl->split_template('about', 'ABOUT');

$tpl->parse("CONTENT", "about");
include_once('../_body.php');