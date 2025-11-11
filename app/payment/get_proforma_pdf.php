<?php

include_once('../_main.php');

$bill = $money->getBill($billId);
if(!$bill){
    $response->redirect($tpl->urlFor('index'));
    die;
}

$pdfClass = new \payment\pdf\proforma\ShopPayment;
$pdfClass->getDocument($billId);