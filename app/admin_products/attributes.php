<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['attributes' => '/admin_products/tpl/attributes.html']);
$tpl->split_template('attributes', 'ATTRIBUTES');

$result = $db->query("SELECT * FROM shop_attributes ORDER BY ATTR_ID DESC")->fetchAll();
foreach($result as $row){

    $tpl->clear_parse("TRANSLATION");
    $tpl->assign("TRANSLATION", "");

    $trans = $db->query("SELECT * FROM shop_attributes_lang WHERE ATTR_ID = ?", $row['ATTR_ID'])->fetchAll();
    $name = '';
    foreach($trans as $row2){
        $name = $row2['ATTR_NAME'];
        $tpl->assign("LANGUAGE", $row2['LANG']);
        $tpl->parse("TRANSLATION", ".translation");
    }

    $tpl->assign("ATTR_NAME", $name);
    $tpl->assign("ATTR_ID", $row['ATTR_ID']);
    $tpl->parse("ATTRIBUTE_ROW", ".attribute_row");
}

$tpl->parse("CONTENT", "attributes");
include_once('../_body_admin.php');