<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['banner_edit' => '/admin/tpl/banner_edit.html']);
$tpl->split_template('banner_edit', 'BANNER_EDIT');

$result = $db->query("SELECT * FROM banners WHERE BANNER_ID = ? AND IS_DELETED = 0", $bannerId)->fetchArray();
if(!$result){
    $response->rediredt($tpl->urlFor("admin/banners"));
    die;
}

$tpl->assign("APP_DOMAIN", APP_DOMAIN);
$tpl->assign_array($result);

$content = @json_decode($result['CONTENT'], true);
if($content){
    $tpl->assign_array($content);
}

$tpl->option_list("LANG_OPTIONS", $result['LANG'], $fullLanguages);

$tpl->parse("CONTENT", "banner_edit");
include_once('../_body_admin.php');