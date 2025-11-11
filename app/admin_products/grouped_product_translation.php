<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['grouped_product_translation' => '/admin_products/tpl/grouped_product_translation.html']);
$tpl->split_template('grouped_product_translation', 'GROUPED_PRODUCT_TRANSLATION');

$result = $db->query("SELECT ITEM_ID, SKU, NEW_SKU, COUNT(*) AS COU FROM shop_products
LEFT JOIN shop_products_lang USING (ITEM_ID)
WHERE LANG = 'lv' AND DESCR != ''
GROUP BY DESCR")->fetchAll();

foreach($result as $row){
    $tpl->assign_array($row);
    $tpl->parse("PRODUCT_ROW", ".product_row");
}

$tpl->parse("CONTENT", "grouped_product_translation");
include_once('../_body_admin.php');