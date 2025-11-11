<?php

chdir(__DIR__);
include_once("../_main_exe.php");

$result = $db->query("SELECT * FROM shop_products_lang WHERE LANG = 'lv'")->fetchAll();
foreach($result as $row){
    $db->query("INSERT IGNORE INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'ru', ?, '', '')", $row['ITEM_ID'], $row['TITLE']);
    $db->query("INSERT IGNORE INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'en', ?, '', '')", $row['ITEM_ID'], $row['TITLE']);
}

