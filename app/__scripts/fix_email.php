<?php 

chdir(__DIR__);
include_once("./../_main_exe.php");

$order = $db->query("SELECT * FROM tbl_order WHERE ORDER_ID = ?", 86)->fetchArray();
if(!$order){
    var_dump(1); die;
}

$bill = $db->query("SELECT * FROM tbl_bill_to_order WHERE ORDER_ID = ?", 86)->fetchArray();            
if($bill){                    

    $currentDate = date('Y-m-d H:i:s');

    $db->query("INSERT INTO tbl_order_confirmed (ORDER_ID, CREATE_DATE) VALUES (?, now()) 
    ON DUPLICATE KEY UPDATE CREATE_DATE = ?", 86, $currentDate);

    $date = substr($currentDate, 0, -9);
    $path = FILE_PRIVATE_PATH . 'tmp/invoice/' . $date;
    @mkdir($path, 0777, true);
    
    $pdfClass = new \payment\pdf\invoice\ShopPayment;
    $pdfClass->saveTmp($path);
    $pdfClass->getDocument($bill['BILL_ID']);
    $path = $pdfClass->getFliePath($bill['BILL_ID']);

    $email = new \email\Email;
    $email->sendTo('order_ready_to_shipment', $order['EMAIL'], [
        'LINK' => APP_DOMAIN . '/profile/order-history/' . $order['ORDER_ID'],
        'ORDER_ID' => $order['ORDER_ID'],
    ], ['Pavadzīme' => $path]); 

    var_dump('success');
} else {
    var_dump(2); die;
} 