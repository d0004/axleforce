<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['cart_page' => '/products/tpl/checkout.html']);
$tpl->split_template('cart_page', 'CART_PAGE');

$document->setMetaTitle("AxleForce | Noformēt pasūtījumu");

$productCart = new \products\ProductCart;
if(!$productCart->itemCount){
    $response->redirect($tpl->urlFor('index'));
    die;
}

$allInStock = true;

$productIds = [];

foreach($productCart->cartProducts as $product){

    $tpl->assign_array($product);
    $tpl->parse("CHECKOUT_PRODUCT_LINE", ".checkout_product_line");

    $reservedCount = $db->query("SELECT SUM(QTY) as QTY FROM tbl_order_product_lock WHERE ITEM_ID = ?", $product['ITEM_ID'])->fetchArray();
    $reservedCount = $reservedCount['QTY'] ?: 0;
    $available = $product['STOCK'] - $reservedCount;

    $tpl->assign("AVAILABLE", $available < 0 ? 0 : $available);

    $imageClass = new \products\ProductImages;
    $image = $imageClass->getMainImage($product['ITEM_ID'], 3);
    $tpl->assign("IMAGE_LINK", $image);

    if($product['QTY'] > $available){
        $allInStock = false;
        $tpl->parse("NOT_IN_STOCK_PRODUCT", ".not_in_stock_product");
    }

    $productIds[$product['ITEM_ID']] = $product['QTY'];
}

$productClass = new \products\Product;
$data = $productClass->getProductFullInfo($productIds);
$tpl->assign("GTM_ITEMS", json_encode($data));


$tpl->assign("ALL_IN_STOCK", $allInStock);

$tpl->option_list("COUNTRY_LIST", 'LV', $countries);


$result = $db->query("SELECT * FROM tbl_user_address WHERE UID = ? ORDER BY IS_DEFAULT DESC LIMIT 1", $user->uid)->fetchArray();
if($result){
    $tpl->assign_array($result);
    $tpl->option_list("COUNTRY_LIST", $result['COUNTRY'], $countries);
} else {
    $tpl->assign_array([
        "NAME" => $user->userData['FNAME'],
        "SURNAME" => $user->userData['LNAME'],
        "EMAIL" => $user->userData['EMAIL'],
    ]);
}

if($user->isLegal){
    $tpl->assign("IS_LEGAL", true);
    $tpl->assign_array($user->userLegal);
}


$tpl->assign_array([
    "CART_SUBTOTAL" => $productCart->totalPrice,
    "SHIPPING_PRICE" => 0.00,
    "TAX_AMOUNT" => 0.00,
    "CART_TOTAL" => $productCart->totalPrice,
]);

$tpl->parse("CONTENT", "cart_page");
include_once('../_body.php');