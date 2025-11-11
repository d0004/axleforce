<?php

include_once(__DIR__ . '/../_main.php');
include_once('./_config.php');

// ini_set('display_errors', 1); 
// ini_set('display_startup_errors', 1); 
// error_reporting(E_ALL);

$filePrivate = new \_class\FilesPrivate(FILE_PRIVATE_PATH);
$filePrivate->setDb($db);

$result = $filePrivate->saveFile("file", $request->files);
if(!$result){
    echo "Error in fole upload. Error (Code: " . $filePrivate->getError() . ")";
    die;
}

ini_set('memory_limit', '-1');
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

$inputFileType = 'Xlsx';

$inputFileName = $filePrivate->getPath($result);
if(!$inputFileName){
    echo "File not found";
    die;
}

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
$reader->setReadDataOnly(true);

$worksheetData = $reader->listWorksheetInfo($inputFileName);
foreach ($worksheetData as $worksheet) {

    $sheetName = $worksheet['worksheetName'];
    $reader->setLoadSheetsOnly($sheetName);
    $spreadsheet = $reader->load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();

    echo "Количество строк - " . count($worksheet->toArray()) . "<br>\n";
    $productCount = 0;
    $inserted = 0;
    $tmp = 0;

    $requiredColumns = [
        0, 
        1, 
        3, 
        5,
    ];

    foreach($worksheet->toArray() as $k => $row){

        if($k == 0) continue;
        foreach($requiredColumns as $column) {
            if($row[$column] == "") continue 2;
        }

        $categories = explode(";", $row[5]);
        if(!$categories){
            echo "Нет категорий | " . $row[0] . "\n<br>";
            continue;
        }

        if($request->post['action'] == 1){
            $result = $db->query("SELECT * FROM shop_products WHERE NEW_SKU = ?", str_replace(" ", "", $row[0]))->fetchArray();
            if($result){
                echo "Дубликат нового артикуля | " . $row[0] . "\n<br>";
                continue;
            }

            $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", str_replace(" ", "", $row[1]))->fetchArray();
            if($result){
                echo "Дубликат артикуля | " . $row[1] . "\n<br>";
                continue;
            }

            $db->query("INSERT INTO shop_products (SKU, NEW_SKU, WEIGHT_ORDER, STOCK, `WEIGHT`) VALUES (?, ?, 0, ?, ?)", str_replace(" ", "", $row[1]), str_replace(" ", "", $row[0]), $row[13] ? (int) $row[13] : 0, $row[14] ? (float) $row[14] : 0);
        } elseif($request->post['action'] == 2) {
            $db->query("REPLACE INTO shop_products (SKU, NEW_SKU, WEIGHT_ORDER, STOCK, `WEIGHT`) VALUES (?, ?, 0, ?, ?)", str_replace(" ", "", $row[1]), str_replace(" ", "", $row[0]), $row[13] ? (int) $row[13] : 0, $row[14] ? (float) $row[14] : 0);
        } else {
            echo "Неизвестное действие\n<br>";
            continue;
        }

        $itemId = $db->lastInsertID();
        if(!$itemId){
            echo "Не удалось добавить в shop_products | " . $row[0] . "\n<br>";
            continue 2;
        }

        foreach($categories as $cat){
            $cat = trim($cat);
            $db->query("INSERT IGNORE INTO shop_products_category (ITEM_ID, CATEGORY_ID) VALUES (? ,?)", $itemId, $cat);
        }

        $additionalSku = explode(';', $row[2]);
        foreach($additionalSku as $sku){
            $sku = trim(str_replace(" ", "", $sku));
            $db->query("INSERT IGNORE INTO shop_products_sku (ITEM_ID, SKU) VALUES (?, ?)", $itemId, $sku);
        }
        
        $db->query("INSERT INTO shop_products_flags (ITEM_ID) VALUES (?)", $itemId);

        $descrShort = trim($row[6]) ?: '';
        $descrFull = trim($row[7]) ?: '';
        $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'lv', ?, ?, ?)", $itemId, $row[0], $descrShort, $descrFull);

        $descrShort = trim($row[8]) ?: '';
        $descrFull = trim($row[9]) ?: '';
        $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'ru', ?, ?, ?)", $itemId, $row[0], $descrShort, $descrFull);

        $descrShort = trim($row[10]) ?: '';
        $descrFull = trim($row[11]) ?: '';
        $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'en', ?, ?, ?)", $itemId, $row[0], $descrShort, $descrFull);

        $db->query("INSERT INTO shop_products_prices (ITEM_ID, STANDART_PRICE, STANDART_PRICE_WITH_VAT, DISCOUNT_PRICE, DISCOUNT_PRICE_WITH_VAT) VALUES (?, ?, ?, ?, ?)", 
            $itemId, 
            $row[3] ? (float) $row[3] : 0.00,
            $row[3]? (float) $row[3] * VAT_AMOUNT_2 : 0.00,
            $row[4] ? (float) $row[4] : 0.00,
            $row[4] ? (float) $row[4] * VAT_AMOUNT_2 : 0.00
        );

        echo "Продукт успешно добавлен | " . $row[0] . "\n<br>";
    }
}