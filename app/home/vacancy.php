<?php

include_once('../_main.php');
include_once('./_config.php');

$document->setMetaTitle("AxleForce | ");

$tpl->define(['vacancy' => '/home/tpl/vacancy.html']);
$tpl->split_template('vacancy', 'VACANCY');



$tpl->parse("CONTENT", "vacancy");
include_once('../_body.php');