<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['index' => '/admin/tpl/index.html']);
$tpl->split_template('index', 'INDEX');


$tpl->parse("CONTENT", "index");
include_once('../_body_admin.php');