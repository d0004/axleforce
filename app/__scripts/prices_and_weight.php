<?php

chdir(__DIR__);
include_once('../_main_exe.php');




$result = $db->query("SELECT * FROM shop_products WHERE WEIGHT <= 0")->fetchAll();
foreach($result as $row){
    echo $row['SKU'] . "\n";
}

die;

$file = FILE_PRIVATE_PATH . 'weight.xlsx';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

$inputFileType = 'Xlsx';

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
$reader->setReadDataOnly(true);

$worksheetData = $reader->listWorksheetInfo($file);
foreach ($worksheetData as $worksheet) {

    $sheetName = $worksheet['worksheetName'];
    $reader->setLoadSheetsOnly($sheetName);
    $spreadsheet = $reader->load($file);
    $worksheet = $spreadsheet->getActiveSheet();

    echo "Количество строк - " . count($worksheet->toArray()) . "<br>\n";
    
    $count = 0;
    $weightProcessed = 0;

    foreach($worksheet->toArray() as $k => $row){
        // echo '<pre>'; print_r($row); echo '</pre>';

        if($row['0'] && $row['7']){

            $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", str_replace([' ', '–', '+', '*'], ['', '-', '', ''], $row['0']))->fetchArray();
            if($result){
                if((float) $row['7']){
                    $db->query("UPDATE shop_products SET WEIGHT = ? WHERE ITEM_ID = ?", (float) $row['7'], $result['ITEM_ID']);
                    $weightProcessed++;
                }
                $count++;
            } else {
                echo $row['0'] . "\n";
            }
        }
    }

    var_dump($count, $weightProcessed);
}
