<?php

$tpl->define([
    'profile_menu' => '/profile/tpl/profile_menu.html'
]);
$tpl->split_template('profile_menu', 'PROFILE_MENU');


$tpl->parse("PROFILE_MENU", "profile_menu");
