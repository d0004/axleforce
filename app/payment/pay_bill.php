<?php

include_once('../_main.php');

$tpl->define(['pay_bill' => '/payment/tpl/pay_bill.html']);
$tpl->split_template('pay_bill', 'PAY_BILL');

$document->setMetaTitle("AxleForce | Apmāksāt pasūtījumu");

$bill = $money->getBill($billId);
if(!$bill){
    $response->redirect($tpl->urlFor('index'));
    die;
}

if($bill['UID'] != $user->uid){
    $response->redirect($tpl->urlFor('index'));
    die;
}

if($bill['STATUS'] != 0){
    $response->redirect($tpl->urlFor('index'));
    die;
}


$taxAmount = 0.00;
$tax = array_filter($bill['detail'], function($ar) {
    return $ar['ITEM'] == 'tax';
});

if($tax){
    foreach($tax as $data){
        if($data['ITEM'] == 'tax'){
            $taxAmount = $data['AMOUNT'];
        }
    }
}

$deliveryAmount = 0.00;
$delivery = array_filter($bill['detail'], function($ar) {
    return $ar['ITEM'] == 'delivery';
});

if($delivery){
    foreach($delivery as $data){
        if($data['ITEM'] == 'delivery'){
            $deliveryAmount = $data['AMOUNT'];
        }
    }
}


$tpl->assign_array([
    "BILL_ID" => $bill['BILL_ID'],
    "BILL_CREATE_DATE" => substr($bill['CREATE_DATE'], 0, -3),
    "BILL_TOTAL_AMOUNT" => $bill['AMOUNT'],
    "PAYMENT_TYPE" => $bill['PAYMENT_TYPE'],
    "EXPIRE_DATE" => substr($bill['EXPIRE_DATE'], 0, -3),
]);


$paymentClass = \payment\payment_method\Factory::getClass($bill['PAYMENT_TYPE']);
if(!($paymentClass instanceof \payment\payment_method\AbstractClass)){
    var_dump("Payment method class not found");
    die;
}

$html = $paymentClass->getPaymentForm($bill['BILL_ID']);
$tpl->assign("PAYMENT_FORM", $html);

$orderId = $db->query("SELECT * FROM tbl_bill_to_order WHERE BILL_ID = ?", $bill['BILL_ID'])->fetchArray();
if(!$orderId){
    var_dump("Order not found");
    die;
}

$order = $db->query("SELECT * FROM tbl_order WHERE ORDER_ID = ? AND UID = ?", $orderId['ORDER_ID'], $user->uid)->fetchArray();
if(!$order){
    var_dump("Order not found");
    die;
}

$tpl->assign_array([
    "ORDER_NAME" => $order['NAME'],
    "ORDER_SURNAME" => $order['SURNAME'],
    "ORDER_EMAIL" => $order['EMAIL'],
    "ORDER_PHONE" => $order['PHONE'],
    "ORDER_SHIPPING_ADDRESS" => $order['SHIPPING_ADDRESS'],
    "ORDER_CITY" => $order['CITY'],
    "ORDER_COUNTRY" => $order['COUNTRY'],
    "ORDER_STATE" => $order['STATE'],
    "ORDER_ZIP" => $order['ZIP'],
   
    "ORDER_COMPANY_VAT" => $order['COMPANY_VAT'],
    "ORDER_COMPANY_NAME" => $order['COMPANY_NAME'],
    "ORDER_COMPANY_ADDRESS" => $order['COMPANY_ADDRESS'],
    "ORDER_ADDRESS" => $order['ADDRESS'],

    "ORDER_AMOUNT" => $order['AMOUNT'],
    "ORDER_TOTAL_AMOUNT" => $order['TOTAL_PRICE'],
    "ORDER_TAX" => $taxAmount,
    "ORDER_DELIVERY_PRICE" => $deliveryAmount,
    
    "ORDER_NOTES" => $order['NOTES'],
]);


$orderDetails = $db->query("SELECT * 
FROM tbl_order_detail 
INNER JOIN shop_products USING(ITEM_ID)
LEFT JOIN shop_products_lang USING (ITEM_ID)
WHERE ORDER_ID = ? AND UID = ? AND LANG = ?", $orderId['ORDER_ID'], $user->uid, $lang)->fetchAll();
foreach($orderDetails as $product){
    $tpl->assign_array($product);
    $tpl->parse("PRODUCT_ROW", ".product_row");
}

$delivery = $db->query("SELECT * FROM tbl_order_delivery WHERE ORDER_ID = ?", $orderId['ORDER_ID'])->fetchArray();
if(!$delivery){
    var_dump("Delivery not found");
    die;
}

// echo '<pre>'; print_r($delivery); echo '</pre>';
$deliveryClass = \delivery\type\Factory::getClass($delivery['DELIVERY_TYPE']);
if(!($deliveryClass instanceof \delivery\type\AbstractType)){
    var_dump("Incorrect delivery");
    die;
}

$deliveryHtml = $deliveryClass->getPayBillView($delivery['DELIVERY_DATA']);
$tpl->assign("DELIVERY_HTML", $deliveryHtml);

$tpl->parse("CONTENT", "pay_bill");
include_once('../_body.php');