<?php

$menu = new \system\Menu();
$menu->desktopCategoryMenu();
$menu->mobileCategoryMenu();
$menu->showMenu();

$document->addStyle('https://fonts.googleapis.com/css?family=Roboto:400,400i,500,500i,700,700i');

$document->addStyle('/assets/vendor/bootstrap/css/bootstrap.css');
$document->addStyle('/assets/vendor/owl-carousel/assets/owl.carousel.min.css');
$document->addStyle('/assets/vendor/photoswipe/photoswipe.css');
$document->addStyle('/assets/vendor/photoswipe/default-skin/default-skin.css');
$document->addStyle('/assets/vendor/select2/css/select2.min.css');
$document->addStyle('/assets/css/style.css?20220929');
$document->addStyle('/assets/css/preloader.css');
$document->addStyle('/assets/css/informers.css');


$document->addStyle('/assets/vendor/fontawesome/css/all.min.css');


$document->addScript('/assets/vendor/jquery/jquery.min.js', true);
$document->addScript('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js', false);
$document->addScript('/assets/vendor/owl-carousel/owl.carousel.min.js', false);
$document->addScript('/assets/vendor/nouislider/nouislider.min.js', false);
$document->addScript('/assets/vendor/photoswipe/photoswipe.min.js', false);
$document->addScript('/assets/vendor/photoswipe/photoswipe-ui-default.min.js', false);
$document->addScript('/assets/vendor/select2/js/select2.min.js', true);
$document->addScript('/assets/vendor/select2/js/i18n/' . $lang . '.js', true);
$document->addScript('/assets/js/number.js', false);
$document->addScript('/assets/js/main.js?8', false);

$document->addScript('/assets/js/jquery.validate.min.js', true);
$document->addScript('/assets/js/jquery.form.min.js', true);

$document->addScript('/assets/js/alerts.js');
// $document->addScript('/assets/js/promise-polyfill.js');

$headerScripts = $document->getScripts();
foreach($headerScripts as $script){
    $tpl->assign("SCRIPT_LINK", $script);
    $tpl->parse("HEADER_SCRIPTS", ".script");
}

$footerScripts = $document->getScripts(false);
foreach($footerScripts as $script){
    $tpl->assign("SCRIPT_LINK", $script);
    $tpl->parse("FOOTER_SCRIPTS", ".script");
}

$styles = $document->getStyles();
foreach($styles as $style){
    $tpl->assign("STYLE_LINK", $style);
    $tpl->parse("STYLES", ".style");
}

$tpl->assign_array([
    "META_TITLE" => $document->getMetaTitle(),
    "META_KEYWORDS" => $document->getMetaKeywords(),
    "META_DESCRIPTION" => $document->getMetaDescription(),
]);

if($user->hasStatus(40)){
    $tpl->define(['admin_menu' => '/system/tpl/admin_menu.html']);
    $tpl->split_template('admin_menu', 'ADMIN_MENU');
    $tpl->parse("ADMIN_MENU", "admin_menu");
}

$informer = $db->query("SELECT * FROM informers WHERE LANG = ? AND STATUS = 1 ORDER BY ID DESC LIMIT 1", $lang)->fetchArray();
if($informer){
    $tpl->define(['informers' => '/system/tpl/informers.html']);
    $tpl->split_template('informers', 'INFORMERS');
    $tpl->assign_array($informer);
    $tpl->parse("INFORMER", "informers");
}

$tpl->parse('FULL_PAGE', 'main');
$html = $tpl->fetch("FULL_PAGE");

echo $html;