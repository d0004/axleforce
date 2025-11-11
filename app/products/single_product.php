<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['single_product' => '/products/tpl/single_product.html']);
$tpl->split_template('single_product', 'SINGLE_PRODUCT');

$product = new \products\Product;
$itemId = $product->getProductBySlug($slug);
if(!$itemId){
    $error = new \_class\Error;
    $error->show404();
    include_once('../_body.php');
    die;
}

$product->getSingleProductView($itemId);

$tpl->parse("CONTENT", "single_product");
include_once('../_body.php');