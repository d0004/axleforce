<?php

include_once("../_main.php");

$xml = new SimpleXMLElement('<root/>');

$category = [];
$result = $db->query("SELECT * 
FROM shop_categories 
INNER JOIN shop_categories_lang USING (CATEGORY_ID)
WHERE LANG = 'lv'")->fetchAll();

$categoryClass = new \category\Category;
foreach($result as $row){
    $category[$row['CATEGORY_ID']] = [
        'title' => $row['TITLE'],
        'url' => APP_DOMAIN . $tpl->urlFor('category/index', ['slug' => $categoryClass->getUrl($row['CATEGORY_ID'])])
    ];
}

$result = $db->query("SELECT * 
FROM shop_products
INNER JOIN shop_products_lang USING (ITEM_ID)
INNER JOIN shop_products_category USING (ITEM_ID)
INNER JOIN shop_products_prices USING (ITEM_ID)
WHERE STATUS = 2 AND DELETED = 0 AND LANG = 'lv'")->fetchAll();

$images = new \products\ProductImages;

foreach($result as $row){

    $shortDescr = strip_tags($row['SHORT_DESCR']);
    $shortDescr = htmlentities($shortDescr, ENT_XML1);

    $track = $xml->addChild('item');
    $track->addChild('name', $row['NEW_SKU'] . ' - ' . $shortDescr);
    $track->addChild('link', APP_DOMAIN . $tpl->urlFor('products/single_product', ['slug' => $row['NEW_SKU']]));

    $mainImage = $images->getMainImage($row['ITEM_ID'], 2);
    $track->addChild('image', APP_DOMAIN . $mainImage);

    $price = $row['STANDART_PRICE_WITH_VAT'];
    if($row['DISCOUNT_PRICE'] > 0){
        $price = $row['DISCOUNT_PRICE_WITH_VAT'];
    }

    $track->addChild('price', $price);
    $track->addChild('manufacturer', 'TruckMaster');

    $reservedCount = $db->query("SELECT SUM(QTY) as QTY FROM tbl_order_product_lock WHERE ITEM_ID = ?", $row['ITEM_ID'])->fetchArray();
    $reservedCount = $reservedCount['QTY'] ?: 0;
    $available = $row['STOCK'] - $reservedCount;
    $available = $available < 0 ? 0 : $available;
    $track->addChild('in_stock', $available);

    $track->addChild('used', 0);
    
    $track->addChild('category', $category[$row['CATEGORY_ID']]['title']);
    $track->addChild('category_full', $category[$row['CATEGORY_ID']]['title']);
    $track->addChild('category_link', $category[$row['CATEGORY_ID']]['url']);
    
    
}

Header('Content-type: text/xml');
print($xml->asXML());