<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['orders' => '/admin/tpl/orders.html']);
$tpl->split_template('orders', 'ORDERS');

$tpl->parse("CONTENT", "orders");
include_once('../_body_admin.php');