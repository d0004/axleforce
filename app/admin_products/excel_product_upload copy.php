<?php

include_once('../_main.php');
include_once('./_config.php');

$filePrivate = new \_class\FilesPrivate(FILE_PRIVATE_PATH);
$filePrivate->setDb($db);

$result = $filePrivate->saveFile("file", $request->files);
if(!$result){
    echo "Error in fole upload. Error (Code: " . $filePrivate->getError() . ")";
    die;
}

ini_set('memory_limit', '-1');
require './../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

$inputFileType = 'Xlsx';

$inputFileName = $filePrivate->getPath($result);
if(!$inputFileName){
    echo "File not found";
    die;
}

// if($request->post['action'] == 4){
//     die;
//     $db->query("TRUNCATE TABLE shop_products");
//     $db->query("TRUNCATE TABLE shop_products_files");
//     $db->query("TRUNCATE TABLE shop_products_flags");
//     $db->query("TRUNCATE TABLE shop_products_lang");
//     $db->query("TRUNCATE TABLE shop_products_prices");
//     $db->query("TRUNCATE TABLE shop_products_relation");

//     echo "!!!!! ВСЯ БАЗА ДАННЫХ ПРОДУКТОВ ОЧИЩЕНА УСПЕШНО !!!!!<br>\n";
// }

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
        2, 
        7,
    ];

    foreach($worksheet->toArray() as $k => $row){
        if($k == 0){
            continue;
        }
        foreach($requiredColumns as $column){
            if($row[$column] == ""){
                continue 2;
            }
        }
    
        switch($request->post['action']){
            // Загрузить только новые товары. Старые не трогать
            case "1":
            case "4":

                $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", str_replace(" ", "", $row[0]))->fetchArray();
                if($result){
                    echo "Дубликат артикуля | " . $row[0] . "\n<br>";
                    continue 2;
                }

                $result = $db->query("SELECT * FROM shop_products WHERE NEW_SKU = ?", str_replace(" ", "", $row[1]))->fetchArray();
                if($result){
                    echo "Дубликат нового артикуля | " . $row[0] . "\n<br>";
                    continue 2;
                }

                $db->query("INSERT INTO shop_products (SKU, NEW_SKU) VALUES (?, ?)", str_replace(" ", "", $row[0]), $row[1]);

                $itemId = $db->lastInsertID();
                if(!$itemId){
                    echo "Не удалось добавить в shop_products | " . $row[0] . "\n<br>";
                    continue 2;
                }

                $categories = explode(";", $row[2]);
                if(!$categories){
                    echo "Нет категорий | " . $row[0] . "\n<br>";
                    continue;
                }

                foreach($categories as $cat){
                    $cat = trim($cat);
                    $db->query("INSERT IGNORE INTO shop_products_category (ITEM_ID, CATEGORY_ID) VALUES (? ,?)", $itemId, $cat);
                }
                
                // $db->query("INSERT INTO shop_products_files (ITEM_ID, FILE_TYPE, FILE, IS_MAIN) VALUES (?, 1, '/assets/images/products/product-1-245x245.jpg', 1)", $itemId);
                $db->query("INSERT INTO shop_products_flags (ITEM_ID) VALUES (?)", $itemId);

                // $enDescription = implode("\n", [$row[8], $row[9], $row[10]]);
                // $ruDescription = implode("\n", [$row[8], $row[9], $row[10]]);
                $lvDescription = implode("\n", [trim($row[5]), trim($row[6])]);

                // $enShortDescr = $row[5] ? $row[5] : '';
                // $ruShortDescr = $row[6] ? $row[6] : $enShortDescr;
                $lvShortDescr = trim($row[3]) ? trim($row[3]) : '';

                // $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'en', ?, ?, ?)", $itemId, $row[1], $enShortDescr, $enDescription);
                // $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'ru', ?, ?, ?)", $itemId, $row[1], $ruShortDescr, $ruDescription);
                $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'lv', ?, ?, ?)", $itemId, $row[1], $lvShortDescr, $lvDescription);

                $db->query("INSERT INTO shop_products_prices (ITEM_ID, STANDART_PRICE) VALUES (?, ?)", $itemId, $row[7]);

            break;
            // Загрузить новые товары. Старые обновить без удаления
            case "2":

            break;
            // Обновить только цены
            // case "3":

            //     $result = $db->query("SELECT * 
            //     FROM shop_products 
            //     INNER JOIN shop_products_prices USING (ITEM_ID)
            //     WHERE SKU = ?", str_replace(" ", "", $row[0]))->fetchArray();
            //     if(!$result){
            //         $result = $db->query("SELECT * 
            //         FROM shop_products 
            //         INNER JOIN shop_products_prices USING (ITEM_ID)
            //         WHERE NEW_SKU = ?", str_replace(" ", "", $row[1]))->fetchArray();   
            //     }
                
            //     if(!$result){
            //         continue 2;
            //     }
                
            //     $db->query("UPDATE shop_products_prices SET STANDART_PRICE = ? WHERE ITEM_ID = ?", $row[7], $result['ITEM_ID']);

            // break;

            // // FOR FIX
            // case "5":

            //     $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", str_replace(" ", "", $row[0]))->fetchArray();
            //     if(!$result){
            //         echo "Товар не найден | " . $row[0] . "\n<br>";
            //         continue 2;
            //     }

            //     $lvShortDescr = trim($row[3]) ? trim($row[3]) : '';
                
            //     $db->query("UPDATE shop_products_lang SET SHORT_DESCR = ? WHERE ITEM_ID = ? AND LANG = 'lv'", $lvShortDescr, $result['ITEM_ID']);
            
            // break;
            
        }
        
    }

    // $db->query("UPDATE shop_products_lang SET DESCR = '' WHERE DESCR = ''");
}