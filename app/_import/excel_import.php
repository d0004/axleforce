<?php

ini_set('memory_limit', '-1');

include_once('../_main.php');

require './../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

$inputFileType = 'Xls';
$inputFileName = APP_DIR . "/_files/import/products2.xls";

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
$reader->setReadDataOnly(true);

$result = $db->query("SELECT CATEGORY_ID FROM shop_categories")->fetchAll();
$categoryes = [];
foreach($result as $row){
    $categoryes[] = $row['CATEGORY_ID'];
}

$db->query("TRUNCATE TABLE shop_products");
$db->query("TRUNCATE TABLE shop_products_lang");
$db->query("TRUNCATE TABLE shop_products_flags");
$db->query("TRUNCATE TABLE shop_products_files");
$db->query("TRUNCATE TABLE shop_products_prices");


$worksheetData = $reader->listWorksheetInfo($inputFileName);
foreach ($worksheetData as $worksheet) {

    $sheetName = $worksheet['worksheetName'];
    $reader->setLoadSheetsOnly($sheetName);
    $spreadsheet = $reader->load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();


    // echo '<pre>'; print_r(); echo '</pre>';

    var_dump("ROW COUNT - " . count($worksheet->toArray()));
    $productCount = 0;
    $inserted = 0;
    $tmp = 0;

    foreach($worksheet->toArray() as $row){
        if($row[0] != "" && $row[1] != "" && $row[2] != "" && $row[5] != "" && $row[6] != "" && $row[7] != ""){
            
            $productCount++;

            $category = 0;
            if(in_array($row[0], $categoryes)){
                $category = $row[0];
            } else {
                if($row[0] == "4.1"){
                    $category = 14;
                } elseif ($row[0] == "4.2"){
                    $category = 15;
                } elseif ($row[0] == "4.3"){
                    $category = 16;
                } elseif ($row[0] == "6.1"){
                    $category = 17;
                } elseif ($row[0] == "6.2"){
                    $category = 18;
                } elseif ($row[0] == "6.3"){
                    $category = 19;
                } elseif ($row[0] == "7.1"){
                    $category = 20;
                } elseif ($row[0] == "7.2"){
                    $category = 25;
                } elseif ($row[0] == "8.1"){
                    $category = 21;
                } elseif ($row[0] == "8.2"){
                    $category = 22;
                } elseif ($row[0] == "10.1"){
                    $category = 23;
                } elseif ($row[0] == "10.2"){
                    $category = 24;
                }
                
            }
                // echo '<pre>'; print_r($row); echo '</pre>';
            if($category){
                $db->query("INSERT IGNORE INTO shop_products (CATEGORY_ID, SKU) VALUES (?, ?)", $category, $row[2]);
                if($db->affectedRows() <= 0){
                    $tmp++;
                    continue;
                }

                $itemId = $db->lastInsertID();
                if(!$itemId){
                    continue;
                }

                $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, ?, ?, ?, ?)", $itemId, 'ru', $row[2], $row[5], $row[5]);
                $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, ?, ?, ?, ?)", $itemId, 'en', $row[2], $row[6], $row[6]);
                $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, ?, ?, ?, ?)", $itemId, 'lv', $row[2], $row[7], $row[7]);
                
                $db->query("INSERT INTO shop_products_flags (ITEM_ID) VALUES (?)", $itemId);
                $db->query("INSERT INTO shop_products_prices (ITEM_ID) VALUES (?)", $itemId);

                if($row[3] == 'NEW'){
                    $db->query("UPDATE shop_products_flags SET NEW = 1 WHERE ITEM_ID = ?", $itemId);
                }

                $db->query("INSERT INTO shop_products_files (ITEM_ID, FILE_TYPE, FILE, IS_MAIN) VALUES (?, ?, ?, ?)", $itemId, 1, '/system/assets/images/products/product-1-245x245.jpg', 1);

                $inserted++;
            }
        }
    }


    var_dump("PRODUCT COUNT - " . $productCount);
    var_dump("INSERTED COUNT - " . $inserted);
    var_dump("DUPLICATE COUNT - " . $tmp);

    die;
}