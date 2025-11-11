<?php

die;

chdir(__DIR__);
include_once('../_main_exe.php');

$bill = $money->getBill('37');

$date = date("Y-m-d");
$path = FILE_PRIVATE_PATH . 'tmp/invoices_email/' . $date;
@mkdir($path, 0777, true);

$pdfClass = new \payment\pdf\invoice\ShopPayment;
$pdfClass->saveTmp($path);
$pdfClass->getDocument($bill['BILL_ID']);
$path = $pdfClass->getFliePath($bill['BILL_ID']);

echo $path;