<?php

include_once('../_main.php');
include_once('./profile_menu.php');

include_once('./../payment/_config.php');

$document->setMetaTitle("AxleForce | Profils");

$tpl->define(['index' => '/profile/tpl/index.html']);
$tpl->split_template('index', 'INDEX');

$tpl->assign("HAS_ADDRESS", false);
$result = $db->query("SELECT * FROM tbl_user_address WHERE UID = ? ORDER BY IS_DEFAULT DESC LIMIT 1", $user->uid)->fetchArray();
if($result){
    $tpl->assign("HAS_ADDRESS", true);
    $tpl->assign_array($result);
}


$result = $db->query("SELECT * FROM tbl_order WHERE UID = ? ORDER BY ORDER_ID DESC LIMIT 4", $user->uid)->fetchAll();
foreach($result as $row){
    $row['CREATE_DATE'] = substr($row['CREATE_DATE'], 0, -3);
    $tpl->assign_array($row);

    $result2 = $db->query("SELECT SUM(QTY) AS ITEM_COUNT FROM tbl_order_detail WHERE ORDER_ID = ?", $row['ORDER_ID'])->fetchArray();
    $tpl->assign("ITEM_COUNT", $result2['ITEM_COUNT'] ? $result2['ITEM_COUNT'] : 0);

    $bill = $db->query("SELECT * FROM tbl_bill_to_order WHERE ORDER_ID = ?", $row['ORDER_ID'])->fetchArray();
    $tpl->assign("BILL_ID", $bill['BILL_ID']);
    
    $tpl->assign("ORDER_STATUS", $orderStatus[$row['STATUS']]);

    $tpl->parse("ORDER_ROW", ".order_row");
}

$tpl->parse("CONTENT", "index");
include_once('../_body.php');