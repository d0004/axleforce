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
            $url1 = "http://catalog.mfilter.lt/api/analogs.php?filterid=" . end($parts);
            storeAnalogs($url1, $row['0']);
        }
    }
}

function storeAnalogs($url, $product)
{
    
    global $db;

    $result = $db->query("SELECT * FROM shop_products WHERE NEW_SKU = ?", $product)->fetchArray();
    if(!$result){
        echo "Product not found | " . $product . "<br>\n";
        return false;
    }

    $db->query("DELETE FROM shop_products_analogs WHERE ITEM_ID = ?", $result['ITEM_ID']);

    // $data = file_get_contents($url . "&page=0");
    // $data = @json_decode($data, true);

    $loop = 0;
    while(1 == 1){
        $data = file_get_contents($url . "&page=" . $loop);
        $data = @json_decode($data, true);
        $loop++;

        if(!is_array($data)) break;

        foreach($data as $analog){
            $db->query("INSERT INTO shop_products_analogs (ITEM_ID, ANALOG_CODE, ANALOG_TYPE, ANALOG_MANUFACTURER, ORIGINAL_CODE)
            VALUES (?, ?, ?, ?, ?)", $result['ITEM_ID'], $analog['cpav'], $analog['t'], $analog['fpav'], $analog['mpav']);
        }
        
        if(count($data) == 0) break;
    }
}