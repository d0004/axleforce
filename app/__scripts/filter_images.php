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
            $parts = explode('/', $url);
            $url1 = "http://catalog.mfilter.lt/api/filters.php?id=" . end($parts);
            $data = file_get_contents($url1);
            $data = @json_decode($data, true);
            $imageUrl = "http://catalog.mfilter.lt/uploads/Image/" . $data['foto'];
         
            // storeImage($imageUrl, $row['0']);
            // die;

        }
    }
}

function storeImage($imageUrl, $product)
{
    die;

    if(!$imageUrl) return false;

    global $db, $imageSizes;

    $fileId = 0;
    $result = $db->query("SELECT MAX(FILE_ID) AS FILE_ID FROM shop_products_files")->fetchArray();
    if($result){
        $fileId = $result['FILE_ID'] + 1;
    }

    $result = $db->query("SELECT * FROM shop_products WHERE NEW_SKU = ?", $product)->fetchArray();
    if(!$result){
        echo "Product not found | " . $product . "<br>\n";
        return false;
    }

    $db->query("DELETE FROM shop_products_files WHERE ITEM_ID = ?", $result['ITEM_ID']);

    $imageUrl = str_replace(' ', '%20', $imageUrl);
    $ext = 'jpg';
    $fileName = time();
    $tmpPath = PUBLIC_DIR . '/files_public/tmp/' . $fileName . '.' . $ext;
    $newDirectory = PUBLIC_DIR . '/files_public/filters/' . $product;

    if(!is_dir($newDirectory)){
        if (!@mkdir($newDirectory, 0777, true)) {
            echo "Не удалось создать директории | {$newDirectory}<br>\n";
            return false;
        }
    }

    $newDirectoryRelative = '/files_public/filters/' . $product;
    file_put_contents($tmpPath, file_get_contents($imageUrl));
    $file = $tmpPath;

    try {
        $image = new ImageResize($file);
        $image->quality_jpg = 85;
        $image->gamma(false);
        $name = $fileName . '-0.' . $ext;
        $image->save($newDirectory . '/' . $name);

        $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, 0)", $fileId, $result['ITEM_ID'], $newDirectoryRelative . '/' . $name);
    } catch (\Exception $e){
        echo "Error | " . $e->getMessage() . " | " . $file . "<br>\n";
    }

    foreach($imageSizes as $size => $data){
        try {
            $image = new ImageResize($file);
            $image->quality_jpg = 85;
            $image->gamma(false);
            $image->resizeToLongSide($data['w']);
            $image->crop($data['w'], $data['h'], true, ImageResize::CROPCENTER);
            $name = $fileName . '-' . $size . '.' . $ext;
            $image->save($newDirectory . '/' . $name);

            $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, ?)", $fileId, $result['ITEM_ID'], $newDirectoryRelative . '/' . $name, $size);
        } catch (\Exception $e){
            echo "Error | " . $e->getMessage() . " | " . $file . "<br>\n";
        }
    }
}