<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['purchase_terms' => "/home/tpl/purchase_terms_$lang.html"]);
$tpl->split_template('purchase_terms', 'PURCHASE_TERMS');

$tpl->parse("CONTENT", "purchase_terms");
include_once('../_body.php');