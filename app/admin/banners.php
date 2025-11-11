<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['banners' => '/admin/tpl/banners.html']);
$tpl->split_template('banners', 'BANNERS');

$result = $db->query("SELECT * FROM banners WHERE IS_DELETED = 0")->fetchAll();
foreach($result as $row){
    $tpl->assign_array($row);
    $tpl->parse("BANNER_ROW", ".banner_row");
}

$tpl->parse("CONTENT", "banners");
include_once('../_body_admin.php');