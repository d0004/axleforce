<?php

include_once('../_main.php');
include_once('./profile_menu.php');

$document->setMetaTitle("AxleForce | Rediģēt profilu");

$tpl->define(['edit_profile' => '/profile/tpl/edit_profile.html']);
$tpl->split_template('edit_profile', 'EDIT_PROFILE');

$tpl->assign_array([
    "USER_FNAME" => $user->userData['FNAME'],
    "USER_LNAME" => $user->userData['LNAME'],
    "USER_EMAIL" => $user->userData['EMAIL'],
    "USER_PHONE" => $user->userInfo['PHONE'],
]);

$tpl->parse("CONTENT", "edit_profile");
include_once('../_body.php');