<?php

include_once('../_main.php');

ini_set('memory_limit', '-1');
require './../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

$inputFileType = 'Xlsx';
$inputFileName = APP_DIR . "/_files/import/import_prices.xlsx";

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
$reader->setReadDataOnly(true);


$worksheetData = $reader->listWorksheetInfo($inputFileName);
foreach ($worksheetData as $worksheet) {

    $sheetName = $worksheet['worksheetName'];
    $reader->setLoadSheetsOnly($sheetName);
    $spreadsheet = $reader->load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();

    var_dump("ROW COUNT - " . count($worksheet->toArray()));
    $productCount = 0;
    $inserted = 0;
    $tmp = 0;

    $requiredColumns = [0, 1, 14];

    foreach($worksheet->toArray() as $k => $row){
            
        if($k == 0){
            continue;
        }

        foreach($requiredColumns as $column){
            if($row[$column] == ""){
                continue 2;
            }
        }
        
        $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", str_replace(" ", "", $row[0]))->fetchArray();
        if(!$result){
            echo $row[0] . " - not found\n<br>";
            continue;
        }

        $db->query("UPDATE shop_products_prices SET STANDART_PRICE = ? WHERE ITEM_ID = ?", $row[14], $result['ITEM_ID']);

    }

    var_dump("PRODUCT COUNT - " . $productCount);
    var_dump("INSERTED COUNT - " . $inserted);
    var_dump("DUPLICATE COUNT - " . $tmp);

    die;
}