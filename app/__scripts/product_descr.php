<?php

chdir(__DIR__);
include_once('../_main_exe.php');

$file = FILE_PRIVATE_PATH . 'descr.xlsx';

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

    foreach($worksheet->toArray() as $k => $row){

        if($row['0'] && $row['1'] && $row['3']){

            $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", str_replace([' ', '–', '+', '*'], ['', '-', '', ''], $row['0']))->fetchArray();
            if($result){ 
                $count++;
                $db->query("UPDATE shop_products_lang SET SHORT_DESCR = ? WHERE ITEM_ID = ? AND LANG = 'en'", $row['1'], $result['ITEM_ID']);
                $db->query("UPDATE shop_products_lang SET SHORT_DESCR = ? WHERE ITEM_ID = ? AND LANG = 'ru'", $row['3'], $result['ITEM_ID']);
            } else {
                echo $row['0'] . "\n";
            }
        }
    }

    var_dump($count);
}
