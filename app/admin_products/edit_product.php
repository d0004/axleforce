<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['edit_product' => '/admin_products/tpl/edit_product.html']);
$tpl->split_template('edit_product', 'EDIT_PRODUCT');

$resultMain = $db->query("SELECT * FROM shop_products WHERE ITEM_ID = ?", $itemId)->fetchArray();
if(!$resultMain){
    die;
}

$tpl->assign("ORIGINAL_ITEM_ID", $resultMain['ITEM_ID']);

$flags = $db->query("SELECT * FROM shop_products_flags WHERE ITEM_ID = ?", $itemId)->fetchArray();
$tpl->assign_array($flags);

$reservedCount = $db->query("SELECT SUM(QTY) as QTY FROM tbl_order_product_lock WHERE ITEM_ID = ?", $itemId)->fetchArray();
$reservedCount = $reservedCount['QTY'] ?: 0;
$available = $resultMain['STOCK'] - $reservedCount;

$tpl->assign_array([
    "LOCKED_COUNT" => $reservedCount,
    "AVAILABLE" => $available,
]);

foreach($fullLanguages as $code => $languageTitle){
    $tpl->assign_array([
        "LANG_CODE" => $code,
        "LANG_TITLE" => $languageTitle,
    ]);
    $tpl->parse("LANGUAGE_BUTTONS", ".language_button");
}

$result = $db->query("SELECT * FROM shop_products_relation WHERE ITEM_ID = ?", $itemId)->fetchAll();
foreach($result as $row){
    $product = $db->query("SELECT * 
    FROM shop_products 
    LEFT JOIN shop_products_prices USING (ITEM_ID)
    WHERE ITEM_ID = ?", $row['RELATED_ITEM_ID'])->fetchArray();
    
    if($product){
        $tpl->assign_array($product);
        $tpl->assign("RELATED_ITEM_ID", $product['ITEM_ID']);
        $tpl->parse("RELATED_PRODUCTS", ".related_products");
    }
}

$tpl->assign_array($resultMain);


$imagesClass = new \products\ProductImages();
$images = $imagesClass->getImages($itemId);

foreach($images as $fileId => $image){
    $tpl->clear_parse("SIZE_EXIST");

    foreach($image as $size => $sizedImage){
        if(isset($imageSizes[$size])){
            $tpl->assign("SIZE", $imageSizes[$size]['w'] . "x" . $imageSizes[$size]['h']);
        } else {
            $tpl->assign("SIZE", 'Original');
        }
        $tpl->parse("SIZE_EXIST", ".size_exist");
    }
    $tpl->assign("IMAGE_LINK", $image[2]);
    $tpl->assign("FILE_ID", $fileId);
    $tpl->parse("PRODUCT_IMAGES", ".product_images"); 
}

$files = $db->query("SELECT * FROM shop_products_files WHERE ITEM_ID = ? AND FILE_TYPE = 2", $itemId)->fetchAll();
foreach($files as $file){
    $tpl->assign_array($file);
    $tpl->assign("FILE_NAME", basename($file['FILE']));
    $tpl->parse("PRODUCT_FILES", ".product_files");
}

$attributes = $db->query("SELECT * 
FROM shop_attributes 
INNER JOIN shop_attributes_lang USING (ATTR_ID)
WHERE LANG = ?", $defaultLang)->fetchAll();

$data = [];
foreach($attributes as $attribute){
    $data[$attribute['ATTR_ID']] = $attribute['ATTR_NAME'];
}
$tpl->option_list("ATTRIBUTES", '', $data);


$productAttributes = $db->query("SELECT * 
FROM shop_products_attributes 
INNER JOIN shop_attributes_lang USING (ATTR_ID)
INNER JOIN shop_attributes_values USING (VAL_ID)
WHERE ITEM_ID = ? AND LANG = ?", $itemId, $defaultLang)->fetchAll();

foreach($productAttributes as $prodAttr){
    $tpl->assign_array($prodAttr);
    $tpl->parse("PRODUCT_ATTRIBUTES", ".product_attributes");
}

$productClass = new \products\Product;
$productCategoriesTmp = $productClass->getProductCategories($itemId);
$productCategories = [];
foreach($productCategoriesTmp as $row){
    $productCategories[] = $row['CATEGORY_ID'];
}

$category = new \category\Category;
$result = $category->buildCategoryTreeAdmin(0);

foreach($result as $row){
    $tpl->assign_array([
        "VALUE" => $row['id'],
        "NAME" => $row['name'],
        "SELECTED" => in_array($row['id'], $productCategories) ? 'selected' : '',
    ]);
    $tpl->parse("CATEGORY_LIST", ".option");
}

$additionalSku = $db->query("SELECT * FROM shop_products_sku WHERE ITEM_ID = ?", $itemId)->fetchAll();
foreach($additionalSku as $row){
    $tpl->assign("ADDITIONAL_SKU", $row['SKU']);
    $tpl->parse("ADDITIONAL_SKU_ROW", ".additional_sku_row");
}


$tpl->parse("CONTENT", "edit_product");
include_once('../_body_admin.php');