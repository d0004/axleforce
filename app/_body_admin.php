<?php

$document->addScript('/assets/vendor/jquery/jquery.min.js');
$document->addStyle('/assets/css/jsTree/style.min.css');
$document->addStyle('/assets/css/preloader.css');
$document->addStyle('/assets/vendor/bootstrap/css/bootstrap46.min.css');
$document->addScript('/assets/js/jstree.min.js');

$document->addScript('/assets/js/alerts.js');
// $document->addScript('/assets/js/promise-polyfill.js');

$document->addScript('/assets/js/libs/sortablejs/sortable.min.js', false);
$document->addScript('/assets/vendor/bootstrap/js/bootstrap46.bundle.min.js', false);
$document->addScript('/assets/js/libs/sortablejs/jquery-sortable.js', false);


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

include_once(APP_DIR  . '/admin/_config.php');

foreach($adminMenuHeader as $title => $content){
    $tpl->clear_parse("ADMIN_MENU_ELEMENT_ITEM");
    $tpl->assign("ADMIN_MENU_TITLE", $title);
    foreach($content as $subItemTitle => $link){
        $tpl->assign_array([
            'SUB_ITEM_TITLE' => $subItemTitle,
            'SUB_ITEM_LINK' => $tpl->urlFor($link),
        ]);
        $tpl->parse("ADMIN_MENU_ELEMENT_ITEM", ".admin_menu_element_item");
    }
    $tpl->parse("ADMIN_MENU_ELEMENT", ".admin_menu_element");
}

$tpl->parse('FULL_PAGE', 'main_admin');
$html = $tpl->fetch("FULL_PAGE");

echo $html;