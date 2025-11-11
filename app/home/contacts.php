<?php

include_once('../_main.php');
include_once('./_config.php');

$document->setMetaTitle("AxleForce | Sazinies ar mums");

$tpl->define(['contacts' => '/home/tpl/contacts.html']);
$tpl->split_template('contacts', 'CONTACTS');

$tpl->parse("CONTENT", "contacts");
include_once('../_body.php');