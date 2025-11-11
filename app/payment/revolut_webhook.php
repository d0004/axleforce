<?php

include_once('../_main.php');
include_once('./_config.php');

$klix = new \payment\payment_method\RevolutPayment;
$klix->webhook($request);

echo json_encode(['success' => true]);
header("HTTP/1.1 200 OK");