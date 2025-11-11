<?php

include_once('../_main.php');
include_once('./_config.php');

if(!isset($query) || !$query){
    $error = new \_class\Error;
    $error->show404();
    include_once('../_body.php');
    die;
}

if(strlen($query) < 3){
    $error = new \_class\Error;
    $error->show404();
    include_once('../_body.php');
    die;
}

$tpl->define(['search' => '/home/tpl/search.html']);
$tpl->split_template('search', 'SEARCH');

$tpl->assign("SEARCH_QUERY", $query);

$productClass = new \products\Product;
$productView = new \products\view\ProductView;

$search = '%' . $query . '%';

$productIds = [];

$result = $db->query("SELECT DISTINCT(ITEM_ID) 
FROM shop_products 
INNER JOIN shop_products_lang USING (ITEM_ID) 
INNER JOIN shop_products_prices USING (ITEM_ID)
WHERE (TITLE LIKE ? OR SKU LIKE ? OR NEW_SKU LIKE ?) 
AND LANG = ? AND STATUS = 2 AND DELETED = 0", $search, $search, $search, $lang)->fetchAll();

foreach($result as $row){
    if(!in_array($row['ITEM_ID'], $productIds))
        $productIds[] = $row['ITEM_ID'];
}

$result = $db->query("SELECT DISTINCT(ITEM_ID) 
FROM shop_products_sku 
INNER JOIN shop_products USING (ITEM_ID)
INNER JOIN shop_products_prices USING (ITEM_ID)
WHERE shop_products_sku.SKU LIKE ? AND STATUS = 2", $search)->fetchAll();

foreach($result as $row){
    if(!in_array($row['ITEM_ID'], $productIds))
        $productIds[] = $row['ITEM_ID'];
}

$result = $db->query("SELECT DISTINCT(ITEM_ID) 
FROM shop_products_analogs
INNER JOIN shop_products USING (ITEM_ID)
INNER JOIN shop_products_prices USING (ITEM_ID)
WHERE shop_products_analogs.ANALOG_CODE LIKE ? AND STATUS = 2 LIMIT 11", $search)->fetchAll();

foreach($result as $row){
    if(!in_array($row['ITEM_ID'], $productIds))
        $productIds[] = $row['ITEM_ID'];
}

$products = $db->query("SELECT * 
FROM shop_products 
INNER JOIN shop_products_lang USING (ITEM_ID) 
INNER JOIN shop_products_prices USING (ITEM_ID)
WHERE ITEM_ID IN (?) AND STATUS = 2 AND LANG = ?", implode(', ', array_values($productIds)), $lang)->fetchAll();

if(count($products)){
    foreach($products as $product){
        $tpl->assign("PRODUCT_CARD", "");
        $tpl->clear_parse("PRODUCT_CARD");

        $productView->getProductCard($product['ITEM_ID'], "PRODUCT_CARD");
        $tpl->parse("PRODUCT_CARDS", ".product_wrap");
    }
} else {
    // $tpl->parse("SEARCH_RESULT_PRODUCT", "nothing_found");
}

$categoryClass = new \category\Category;

$imageClass = new \products\CategoryImages;

$categories = $db->query("SELECT * 
FROM shop_categories_lang 
WHERE TITLE LIKE ? AND LANG = ?", $search, $lang)->fetchAll();

if(count($categories)){
    foreach($categories as $category){
        $tpl->assign_array($category);
        $tpl->assign("URL_SLUG", $categoryClass->getUrl($category['CATEGORY_ID'])); 
        
        $image = $imageClass->getMainImage($category['CATEGORY_ID'], 2);
        $tpl->assign("CATEGORY_IMAGE", $image);

        $tpl->parse("SEARCH_CATEGORY", ".search_category");
    }
} else {
    // $tpl->parse("SEARCH_RESULT_CATEGORY", "nothing_found");
}

$tpl->parse("CONTENT", "search");
include_once('../_body.php');