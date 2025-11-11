<?php

include_once('../_main.php');
include_once('./profile_menu.php');

$document->setMetaTitle("AxleForce | Rediģēt adresi");

$tpl->define(['edit_profile' => '/profile/tpl/address_edit.html']);
$tpl->split_template('edit_profile', 'EDIT_PROFILE');

$tpl->option_list("COUNTRY_LIST", 'LV', $countries);

if($recId){
    $result = $db->query("SELECT * FROM tbl_user_address WHERE UID = ? AND REC_ID = ?", $user->uid, $recId)->fetchArray();
    if($result){
        $tpl->assign_array($result);
        $tpl->option_list("COUNTRY_LIST", $result['COUNTRY'], $countries);
    }
}

$tpl->parse("CONTENT", "edit_profile");
include_once('../_body.php');