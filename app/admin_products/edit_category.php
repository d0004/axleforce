<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['index' => '/admin_products/tpl/edit_category.html']);
$tpl->split_template('index', 'INDEX');

$result = $db->query("SELECT * FROM shop_categories WHERE CATEGORY_ID = ?", $categoryId)->fetchArray();
if(!$result){
    $response->redirect($tpl->urlFor('admin_products/index'));    
    die;
}

$tpl->assign_array($result);

foreach($fullLanguages as $code => $languageTitle){
    $tpl->assign_array([
        "LANG_CODE" => $code,
        "LANG_TITLE" => $languageTitle,
    ]);
    $tpl->parse("LANGUAGE_BUTTONS", ".language_button");
}

$imagesClass = new \products\CategoryImages();
$images = $imagesClass->getImages($categoryId);

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

$tpl->parse("CONTENT", "index");
include_once('../_body_admin.php');