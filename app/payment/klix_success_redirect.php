<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['success_payment_html' => '/payment/tpl/success_payment.html']);
$tpl->split_template('success_payment_html', 'SUCCESS_PAYMENT_HTML');

$bill = $money->getBill($billId);


if($bill){

    // echo "<pre>" . print_r($bill, 2) . "</pre>"; die;

    $order = $db->query("SELECT * 
    FROM tbl_bill_to_order 
    INNER JOIN tbl_order USING (ORDER_ID)
    WHERE BILL_ID = ?", $bill['BILL_ID'])->fetchArray();

    if($order){
        $productIds = [];
        $result = $db->query("SELECT * FROM tbl_order_detail WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchAll();
        foreach($result as $row){
            $productIds[$row['ITEM_ID']] = $row['QTY'];
        }

        $productClass = new \products\Product;
        $data = $productClass->getProductFullInfo($productIds);
        if($data){

            $tpl->assign_array([
                "GTM_BILL" => $bill['BILL_ID'],
                "GTM_PRODUCT_PRICE" => 0.00,
                "GTM_DELIVERY_PRICE" => 0.00,
                "GTM_TAX" => 0.00,
            ]);

            foreach($bill['detail'] as $detail){
                if($detail['ITEM'] == 'products')
                    $tpl->assign("GTM_PRODUCT_PRICE", $detail['AMOUNT']);
                    
                if($detail['ITEM'] == 'delivery')
                    $tpl->assign("GTM_DELIVERY_PRICE", $detail['AMOUNT']);

                if($detail['ITEM'] == 'tax')
                    $tpl->assign("GTM_TAX", $detail['AMOUNT']);
            }

            $tpl->assign("HAS_GTM_ITEMS", true);
            $tpl->assign("GTM_ITEMS", json_encode($data));
        }
    }

}

$featuredSlider = new \products\view\FeaturedSlider();
$featuredSlider->getView("FEATURED_SLIDER");

$newSlider = new \products\view\NewProductBlock();
$newSlider->getView("NEW_SLIDER");

$tpl->parse("PAGE_CONTENT", "success_payment");
$tpl->parse("CONTENT", "success_payment_html");
include_once('../_body.php');