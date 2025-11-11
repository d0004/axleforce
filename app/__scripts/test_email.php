<?php

chdir(__DIR__);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("../_main.php");

// $email = new email\Email;
// $email->sendTo('after_registration', 'dan1617laz@gmail.com');


$checkVat = new \_class\CheckVat;

$vat = 'LV90011787071';
$name = '';
$address = '';
$error = '';

$checkVat->check($vat, $name, $address, $error);

var_dump($vat, $name, $address, $error);