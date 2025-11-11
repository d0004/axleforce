<?php

include_once('../_main.php');
include_once('./profile_menu.php');

$document->setMetaTitle("AxleForce | Manas adreses");

$tpl->define(['edit_profile' => '/profile/tpl/address.html']);
$tpl->split_template('edit_profile', 'EDIT_PROFILE');

$result = $db->query("SELECT * FROM tbl_user_address WHERE UID = ?", $user->uid)->fetchAll();
foreach($result as $row){
    $tpl->assign_array($row);
    $tpl->parse("ADDRESS_BLOCK", ".address_block");
}

$tpl->parse("CONTENT", "edit_profile");
include_once('../_body.php');