<?php

chdir(__DIR__);
include_once("../_main_exe.php");

$uid = $argv[1];

$db->query("DELETE FROM tbl_user WHERE UID = ?", $uid);
$db->query("DELETE FROM tbl_user_info WHERE UID = ?", $uid);
$db->query("DELETE FROM tbl_user_legal WHERE UID = ?", $uid);
$db->query("DELETE FROM tbl_user_status WHERE UID = ?", $uid);
$db->query("DELETE FROM tbl_user_address WHERE UID = ?", $uid);

$orders = $db->query("SELECT * FROM tbl_order WHERE UID = ?", $uid)->fetchAll();
foreach($orders as $order){
    $db->query("DELETE FROM tbl_order WHERE ORDER_ID = ?", $order['ORDER_ID']);
    $db->query("DELETE FROM tbl_order_detail WHERE ORDER_ID = ?", $order['ORDER_ID']);
    $db->query("DELETE FROM tbl_order_admin_status WHERE ORDER_ID = ?", $order['ORDER_ID']);
    $db->query("DELETE FROM tbl_order_product_lock WHERE ORDER_ID = ?", $order['ORDER_ID']);
    $db->query("DELETE FROM tbl_order_omniva_track WHERE ORDER_ID = ?", $order['ORDER_ID']);
}

$bills = $db->query("SELECT * FROM tbl_bill WHERE UID = ?", $uid)->fetchAll();
foreach($bills as $bill){
    $db->query("DELETE FROM tbl_bill WHERE BILL_ID = ?", $bill['BILL_ID']);
    $db->query("DELETE FROM tbl_bill_detail WHERE BILL_ID = ?", $bill['BILL_ID']);
    $db->query("DELETE FROM tbl_bill_to_order WHERE ORDER_ID = ?", $bill['BILL_ID']);
}