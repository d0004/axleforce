<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['informers' => '/admin/tpl/informers.html']);
$tpl->split_template('informers', 'INFORMERS');

$result = $db->query("SELECT * FROM informers ORDER BY ID DESC, STATUS DESC")->fetchAll();
foreach($result as $row){
    $tpl->assign_array($row);
    $tpl->parse("INFORMERS_" . strtoupper($row['LANG']), ".informer_row");
}

$tpl->parse("CONTENT", "informers");
include_once('../_body_admin.php');