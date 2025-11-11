<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['category_products' => '/admin_products/tpl/category_products.html']);
$tpl->split_template('category_products', 'CATEGORY_PRODUCTS');

$category = $db->query("SELECT * 
FROM shop_categories
LEFT JOIN shop_categories_lang USING (CATEGORY_ID)
WHERE CATEGORY_ID = ?", $categoryId)->fetchArray();

$tpl->assign_array([
    "CATEGORY_ID" => $categoryId,
    "CATEGORY_TITLE" => $category['TITLE'],
]);

$products = $db->query("SELECT * 
FROM shop_products 
INNER JOIN shop_products_category USING (ITEM_ID)
LEFT JOIN shop_products_prices USING (ITEM_ID)
LEFT JOIN shop_products_flags USING (ITEM_ID)
WHERE CATEGORY_ID = ? AND DELETED = 0", $category['CATEGORY_ID'])->fetchAll();

foreach($products as $product){

    // var_dump($product);

    $productLangs = $db->query("SELECT DISTINCT LANG FROM shop_products_lang WHERE ITEM_ID = ?", $product['ITEM_ID'])->fetchAll();
    
    $langArr = [];
    foreach($productLangs as $prLang){
        $langArr[] = $prLang['LANG'];
    }

    $tpl->assign("PRODUCT_LANGS", implode(", ", $langArr));

    $tpl->assign_array($product);
    $tpl->parse("PRODUCT_ROWS", ".product_rows");
}

$tpl->parse("CONTENT", "category_products");
include_once('../_body_admin.php');