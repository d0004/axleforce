<?php

include_once('../_main.php');

$tpl->define(['stripe_cancel' => '/payment/tpl/stripe_cancel.html']);
$tpl->split_template('stripe_cancel', 'STRIPE_CANCEL');

$tpl->parse("CONTENT", "stripe_cancel");
include_once('../_body.php');