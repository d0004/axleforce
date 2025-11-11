<?php

chdir(__DIR__);
include_once("../_main_exe.php");

$exportJson = @file_get_contents(FILE_PRIVATE_PATH . '/export.json');
if(!$exportJson){
    var_dump('Error 1'); die;
}

$data = (array) @json_decode($exportJson, true);
if(!$data){
    var_dump('Error 2'); die;
}

$data = array_shift($data['stockOffices']);

$totalProducts = 0;
$updatedProducts = 0;
$notFound = [];

foreach($data['stockProducts'] as $product){
    $totalProducts++;
    $result = $db->query("SELECT * FROM shop_products WHERE NEW_SKU = ?", $product['code'])->fetchArray();
    if($result){
        $db->query("UPDATE shop_products SET STOCK = ? WHERE ITEM_ID = ?", $product['productQuantity'], $result['ITEM_ID']);
        $updatedProducts++;
    } else {
        $notFound[] = $product['code'];
    }
    
}

var_dump($totalProducts, $updatedProducts);
echo "<pre>"; print_r($notFound); echo "</pre>";