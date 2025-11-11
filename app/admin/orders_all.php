<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['orders_all' => '/admin/tpl/orders_all.html']);
$tpl->split_template('orders_all', 'ORDERS_ALL');

// $result = $db->query("SELECT * 
// FROM tbl_bill
// INNER JOIN tbl_bill_to_order USING (BILL_ID)
// INNER JOIN tbl_order USING (ORDER_ID)")->fetchAll();

$result = $db->query("SELECT tbl_bill.*, tbl_bill_to_order.*, tbl_bill_flag.FLAG 
FROM tbl_bill 
INNER JOIN tbl_bill_to_order USING (BILL_ID) 
LEFT JOIN tbl_bill_flag ON (tbl_bill.BILL_ID = tbl_bill_flag.BILL_ID AND tbl_bill_flag.FLAG = 'confirmed')
ORDER BY tbl_bill.BILL_ID DESC")->fetchAll();

foreach($result as $row){
    $tpl->assign_array($row);
    $tpl->parse("BILL_ROW", ".bill_row");
}

$tpl->parse("CONTENT", "orders_all");
include_once('../_body_admin.php');