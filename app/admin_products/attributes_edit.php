<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['attributes_edit' => '/admin_products/tpl/attributes_edit.html']);
$tpl->split_template('attributes_edit', 'ATTRIBUTES_EDIT');

$result = $db->query("SELECT * FROM shop_attributes WHERE ATTR_ID = ?", $attrId)->fetchArray();
if(!$result){
    $response->redirect($tpl->urlFor('admin_products/attributes'));
    die;
}

$tpl->assign("ATTRIBUTE_ID", $attrId);

foreach($languages as $language){
    $tpl->assign("LANGUAGE", $language);
    $trans = $db->query("SELECT * FROM shop_attributes_lang WHERE ATTR_ID = ? AND LANG = ?", $attrId, $language)->fetchArray();
    $tpl->assign_array($trans);
    $tpl->parse("TRANS_ROW", ".trans_row");
}

$values = $db->query("SELECT * FROM shop_attributes_values WHERE ATTR_ID = ?", $attrId)->fetchAll();
foreach($values as $value){
    $tpl->assign_array($value);
    $tpl->parse("ATTR_VALUES", ".attr_values");
}

$tpl->parse("CONTENT", "attributes_edit");
include_once('../_body_admin.php');