<?php

chdir(__DIR__);
include_once("../_main_exe.php");

$result = $db->query("SELECT *
FROM tbl_bill
INNER JOIN tbl_bill_to_order USING (BILL_ID)
WHERE EXPIRE_DATE < now() AND STATUS = 0")->fetchAll();

$products = [];
$productsIds = [];

$canceled = 0;

foreach($result as $row){

    $check = $db->query("SELECT * FROM tbl_bill_flag WHERE BILL_ID = ? AND FLAG = 'confirmed'", $row['BILL_ID'])->fetchArray();
    if($check) continue;

    $db->query("UPDATE tbl_bill SET STATUS = 9 WHERE BILL_ID = ?", $row['BILL_ID']);
    $db->query("UPDATE tbl_order SET STATUS = 9 WHERE ORDER_ID = ?", $row['ORDER_ID']);

    $tmp = $db->query("SELECT * 
    FROM tbl_order_product_lock
    INNER JOIN shop_products USING (ITEM_ID)
    ")->fetchAll();

    foreach($tmp as $row2){
        $productsIds[$row2['NEW_SKU']] = $row2['ITEM_ID'];
        if(isset($products[$row2['NEW_SKU']])){
            $products[$row2['NEW_SKU']] += $row2['QTY'];
        } else {
            $products[$row2['NEW_SKU']] = $row2['QTY'];
        }
    }

    $db->query("DELETE FROM tbl_order_product_lock WHERE ORDER_ID = ?", $row['ORDER_ID']);

    $canceled ++;
}

if($canceled > 0){
    $telegram = new \_class\TelegramBot;
    $message = "Дата оплаты истекла у {$count} счетов.\n\nОсвобождены следующие товары:\n";
    foreach($products as $sku => $qty){

        $product = $db->query("SELECT * FROM shop_products WHERE ITEM_ID = ?", $productsIds[$sku])->fetchArray();
        $reservedCount = $db->query("SELECT SUM(QTY) as QTY FROM tbl_order_product_lock WHERE ITEM_ID = ?", $productsIds[$sku])->fetchArray();
        $reservedCount = $reservedCount['QTY'] ?: 0;
        $available = $product['STOCK'] - $reservedCount;

        $message .= "<b>{$sku}</b> - {$qty} штук(-а) | Остаток: {$available}\n";
    }
    $telegram->sendMessage($message);
}