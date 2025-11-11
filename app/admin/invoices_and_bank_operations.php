<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['invoices_and_bank_operations' => '/admin/tpl/invoices_and_bank_operations.html']);
$tpl->split_template('invoices_and_bank_operations', 'INVOICES_AND_BANK_OPERATIONS');


$tpl->parse("CONTENT", "invoices_and_bank_operations");
include_once('../_body_admin.php');