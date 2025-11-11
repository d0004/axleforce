<?php

chdir(__DIR__);
include_once('../_main_exe.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \Gumlet\ImageResize;

$spreadsheet = new Spreadsheet();

$inputFileType = 'Xlsx';

$inputFileName = __DIR__ . '/filters.xlsx';

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
$reader->setReadDataOnly(false);

$worksheetData = $reader->listWorksheetInfo($inputFileName);
foreach ($worksheetData as $worksheet) {

    $sheetName = $worksheet['worksheetName'];
    $reader->setLoadSheetsOnly($sheetName);
    $spreadsheet = $reader->load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();

    foreach($worksheet->toArray() as $k => $row){
        if($k == 0) continue;

        $url = $worksheet->getHyperlink('M' . ($k + 1))->getUrl();
        if($url){
            // echo '<pre>' . print_r($url, 2) . '</pre>'; die;
            $parts = explode('/', $url);
            $url1 = "http://catalog.mfilter.lt/api/filters.php?id=" . end($parts);
            storeRecord($url1, $row['0']);
        }
    }
}

function storeRecord($url, $product)
{
    global $db;

    $data = file_get_contents($url);
    $data = @json_decode($data, true);

    if(!is_array($data)) return;


    $result = $db->query("SELECT * FROM shop_products WHERE NEW_SKU = ?", $product)->fetchArray();
    if(!$result){
        echo "Product not found | " . $product . "<br>\n";
        return false;
    }

    // echo '<pre>' . print_r($data, 2) . '</pre>';

    $db->query("REPLACE INTO shop_products_filter_sizes (ITEM_ID, A, B, C, D, E, F, G, H)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
        $result['ITEM_ID'], 
        (float) trim($data['a']) ?? 0, 
        (float) trim($data['b']) ?? 0, 
        (float) trim($data['c']) ?? 0, 
        (float) trim($data['d']) ?? 0, 
        (float) trim($data['e']) ?? 0, 
        (float) trim($data['f']) ?? 0, 
        (float) trim($data['g']) ?? 0, 
        (float) trim($data['h']) ?? 0
);
   
}