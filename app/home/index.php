<?php

include_once('../_main.php');
include_once('./_config.php');


$document->setMetaTitle("AxleForce | Kravas automašīnu rezerves daļas");

$tpl->define(['index' => '/home/tpl/index.html']);
$tpl->split_template('index', 'INDEX');

$featuredSlider = new \products\view\FeaturedSlider();
$featuredSlider->getView("FEATURED_SLIDER");

$salesBlock = new \products\view\SalesBlock();
$salesBlock->getView("SPECIAL_DEALS");

$hotBlock = new \products\view\HotSlider();
$hotBlock->getView("HOT_DEALS");

$newBlock = new \products\view\NewProductBlock();
$newBlock->getView("NEWPRODUCT_SLIDER");

$categoryBlock = new \products\view\CategoryProducts();
if($categoryBlock->setCategory(2)){
    $categoryBlock->setImage("/files_public/images/darba_lukturi_led.jpg");
    $categoryBlock->getView("CATEGORY_PRODUCT_BLOCK");
}

$categoryBlock = new \products\view\CategoryProducts();
if($categoryBlock->setCategory(9)){
    $categoryBlock->setImage("/files_public/images/atstarojosas_ierices.jpg");
    $categoryBlock->getView("CATEGORY_PRODUCT_BLOCK_2");
}

$category = new \category\Category;
$tpl->assign_array([
    "CATEGORY_LINK" => $category->getUrl(2),
    "CATEGORY_4_LINK" => $category->getUrl(4),
    "CATEGORY_44_LINK" => $category->getUrl(44),
    "CATEGORY_6_LINK" => $category->getUrl(6),
    "CATEGORY_12_LINK" => $category->getUrl(12),
]);

$banners = $db->query("SELECT * FROM banners WHERE LANG = ? AND IS_ACTIVE = 1 AND IS_DELETED = 0", $lang)->fetchAll();
foreach($banners as $banner){
    $tpl->assign_array($banner);
    $content = @json_decode($banner['CONTENT'], true);
    if($content){
        $tpl->assign_array($content);
    }

    $images = new \products\ProductImages;
    $image = $images->getMainImage($banner['ITEM_ID'], 0);
    if($image){
        $tpl->assign("BANNER_ITEM_ID", $banner['ITEM_ID']);
        $tpl->assign("BANNER_IMAGE", $image);
    }

    $tpl->parse("BANNER_SLIDE", ".banner_slide");
}

$tpl->parse("CONTENT", "index");
include_once('../_body.php');