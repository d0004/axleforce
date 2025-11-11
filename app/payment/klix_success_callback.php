<?php

include_once('../_main.php');
include_once('./_config.php');

$klix = new \payment\payment_method\KlixPayment;
$klix->successCallback($billId, $request);
