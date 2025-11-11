<?php

chdir(__DIR__);
include_once("../_main_exe.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Helper\Html;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$result = $db->query("SELECT *
FROM shop_products
INNER JOIN shop_products_lang USING (ITEM_ID)")->fetchAll();

$wizard = new Html;

foreach($result as $i => $row){
    $num = $i + 1;
    $sheet->setCellValue('A' . $num, $row['SKU']);    
    $sheet->setCellValue('B' . $num, $row['NEW_SKU']); 
    
    $richText = $wizard->toRichTextObject(nl2br($row['SHORT_DESCR']));
    $sheet->setCellValue('C' . $num, $richText);    

    $richText = $wizard->toRichTextObject(nl2br($row['DESCR']));
    $sheet->setCellValue('D' . $num, $richText);    
}

$writer = new Xlsx($spreadsheet);
$writer->save('export.xlsx');