<?php

include_once('../_main.php');
include_once('./_config.php');

$document->setMetaTitle("AxleForce | Pirkumu grozs");

$tpl->define(['cart_page' => '/products/tpl/cart_page.html']);
$tpl->split_template('cart_page', 'CART_PAGE');

$productCart = new \products\ProductCart;
// if(!$productCart->itemCount){
//     var_dump('Todo: Empty cart');
//     die;
// }

// echo '<pre>'; print_r($productCart->cartProducts); echo '</pre>'; die;

foreach($productCart->cartProducts as $product){
    $tpl->assign_array($product);
    
    $imageClass = new \products\ProductImages;
    $image = $imageClass->getMainImage($product['ITEM_ID'], 3);
    $tpl->assign("CART_IMAGE_LINK", $image);

    $tpl->parse("FULL_CART_PRODUCT_LINE", ".full_cart_product_line");
}

$tpl->assign_array([
    "CART_SUBTOTAL" => $productCart->totalPrice,
    "SHIPPING_PRICE" => 0.00,
    "TAX_AMOUNT" => 0.00,
    "CART_TOTAL" => $productCart->totalPrice,
]);

$tpl->parse("CONTENT", "cart_page");
include_once('../_body.php');