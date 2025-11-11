<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['success_payment_html' => '/payment/tpl/error_payment.html']);
$tpl->split_template('success_payment_html', 'SUCCESS_PAYMENT_HTML');

$featuredSlider = new \products\view\FeaturedSlider();
$featuredSlider->getView("FEATURED_SLIDER");

$newSlider = new \products\view\NewProductBlock();
$newSlider->getView("NEW_SLIDER");

$tpl->parse("PAGE_CONTENT", "error_in_payment");
$tpl->parse("CONTENT", "success_payment_html");
include_once('../_body.php');