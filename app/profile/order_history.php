<?php

include_once('../_main.php');
include_once('./profile_menu.php');

include_once('./../payment/_config.php');

$document->setMetaTitle("AxleForce | Pasūtījumu vēsture");

$tpl->define(['index' => '/profile/tpl/order_history.html']);
$tpl->split_template('index', 'INDEX');

if($orderId){

    $order = $db->query("SELECT * FROM tbl_order WHERE ORDER_ID = ? AND UID = ?", $orderId, $user->uid)->fetchArray();
    if(!$order){
        $response->redirect($tpl->urlFor('profile/order_history'));
        die;
    }

    $bill = $db->query("SELECT * 
    FROM tbl_bill_to_order
    INNER JOIN tbl_bill USING (BILL_ID)
    WHERE ORDER_ID = ?", $orderId)->fetchArray();

    $bill = $money->getBill($bill['BILL_ID']);

    if(!$bill){
        $response->redirect($tpl->urlFor('profile/order_history'));
        die;
    }

    $tpl->assign("BILL_ID", $bill['BILL_ID']);

    if($bill['STATUS'] == 0){
        $paymentClass = \payment\payment_method\Factory::getClass($bill['PAYMENT_TYPE']);
        if(!($paymentClass instanceof \payment\payment_method\AbstractClass)){
            var_dump("Payment method class not found");
            die;
        }

        $html = $paymentClass->getPaymentForm($bill['BILL_ID']);
        $tpl->assign("PAYMENT_FORM", $html);
    }

    $delivery = $db->query("SELECT * FROM tbl_order_delivery WHERE ORDER_ID = ?", $orderId)->fetchArray();
    if($delivery){
        $deliveryClass = \delivery\type\Factory::getClass($delivery['DELIVERY_TYPE']);
        if($deliveryClass instanceof \delivery\type\AbstractType){
            $deliveryHtml = $deliveryClass->getPayBillView($delivery['DELIVERY_DATA']);
            $tpl->assign("DELIVERY_HTML", $deliveryHtml);       
        }
    }

    $tpl->assign_array([
        "BILL_ID" => $bill['BILL_ID'],
        "CREATE_DATE" => substr($bill['CREATE_DATE'], 0, -3),
        "EXPIRE_DATE" => substr($bill['EXPIRE_DATE'], 0, -3),
        "BILL_TOTAL_AMOUNT" => $bill['AMOUNT'],
        "BILL_STATUS" => $bill['STATUS'],
    ]);

    if(in_array($order['STATUS'], [0, 2, 9])){
        $tpl->parse("ORDER_STATUS_TPL", "order_status_{$order['STATUS']}");
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

    // var_dump($bill); die;

    $tpl->assign_array([
        "ORDER_ID" => $order['ORDER_ID'],
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

        "ORDER_TOTAL_AMOUNT" => $order['AMOUNT'],
        "ORDER_TAX" => $taxAmount,
        "ORDER_DELIVERY_PRICE" => $deliveryAmount,
        
        "ORDER_NOTES" => $order['NOTES'],
        "ORDER_STATUS" => $order['STATUS'],
    ]);

    $orderDetails = $db->query("SELECT * 
    FROM tbl_order_detail 
    LEFT JOIN shop_products_lang USING (ITEM_ID)
    WHERE ORDER_ID = ? AND UID = ? AND LANG = ?", $orderId, $user->uid, $lang)->fetchAll();
    foreach($orderDetails as $product){
        $tpl->assign_array($product);
        $tpl->parse("PRODUCT_ROW", ".product_row");
    }

    $tpl->parse("PAGE_CONTENT", "detail");

} else {

    $result = $db->query("SELECT *, tbl_bill.AMOUNT AS TOTAL_ORDER_AMOUNT, tbl_order.STATUS AS ORDER_STATUS
    FROM tbl_order 
    INNER JOIN tbl_bill_to_order USING (ORDER_ID)
    INNER JOIN tbl_bill USING (BILL_ID)
    WHERE tbl_order.UID = ? 
    ORDER BY ORDER_ID DESC", $user->uid)->fetchAll();

    foreach($result as $row){
        $row['CREATE_DATE'] = substr($row['CREATE_DATE'], 0, -3);
        $tpl->assign_array($row);

        $tpl->assign("BILL_ID", $row['BILL_ID']);
        if(in_array($row['ORDER_STATUS'], [0, 2, 9])){
            $tpl->parse("ORDER_STATUS_TPL", "order_status_{$row['ORDER_STATUS']}");
        }
        
        $tpl->parse("ORDER_ROW", ".order_row");
    }

    $tpl->parse("PAGE_CONTENT", "list");
}

$tpl->parse("CONTENT", "index");
include_once('../_body.php');