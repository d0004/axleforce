<?php

include_once('../_main.php');
include_once('./_config.php');

$delivery = new \delivery\type\LatvijasPasts;
$label = $delivery->label($parcel);
if($label){
    header("Content-type:application/pdf");
    echo $label;
    die;
}

echo "Error";
die;