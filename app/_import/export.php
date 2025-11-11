<?php

ini_set('memory_limit', '-1');
include_once('../_main.php');

require './../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$letters = [];
foreach (range('A', 'Z') as $char) {
    $letters[] = $char;
}
$rowNum = 1;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue($letters[0] . $rowNum, 'Свой артикуль');
$sheet->setCellValue($letters[1] . $rowNum, 'Оригинальный артикуль');
$sheet->setCellValue($letters[2] . $rowNum, 'Номер категории');
$sheet->setCellValue($letters[3] . $rowNum, 'Фото или пустота');
$sheet->setCellValue($letters[4] . $rowNum, 'Название EN');
$sheet->setCellValue($letters[5] . $rowNum, 'Название LV');
$sheet->setCellValue($letters[6] . $rowNum, 'Название RU');
$sheet->setCellValue($letters[7] . $rowNum, 'Короткое описание EN');
$sheet->setCellValue($letters[8] . $rowNum, 'Короткое описание LV');
$sheet->setCellValue($letters[9] . $rowNum, 'Короткое описание RU');
$sheet->setCellValue($letters[10] . $rowNum, 'Описание EN');
$sheet->setCellValue($letters[11] . $rowNum, 'Описание LV');
$sheet->setCellValue($letters[12] . $rowNum, 'Описание RU');
$sheet->setCellValue($letters[13] . $rowNum, 'Цена');

$rowNum++;

$result = $db->query("SELECT * 
FROM shop_products
LEFT JOIN shop_products_prices USING (ITEM_ID)")->fetchAll();

foreach($result as $row){

    // echo '<pre>'; print_r($row); echo '</pre>';
    $trans = [];
    $result2 = $db->query("SELECT * FROM shop_products_lang WHERE ITEM_ID = ?", $row['ITEM_ID'])->fetchAll();
    foreach($result2 as $lang){
        $trans[$lang['LANG']] = $lang;
    }
    // echo '<pre>'; print_r($trans); echo '</pre>'; die;

    $sheet->setCellValue($letters[0] . $rowNum, '');
    $sheet->setCellValue($letters[1] . $rowNum, $row['SKU']);
    $sheet->setCellValue($letters[2] . $rowNum, $row['CATEGORY_ID']);
    $sheet->setCellValue($letters[3] . $rowNum, '');

    $sheet->setCellValue($letters[4] . $rowNum, isset($trans['en']['TITLE']) ? $trans['en']['TITLE'] : '');
    $sheet->setCellValue($letters[5] . $rowNum, isset($trans['lv']['TITLE']) ? $trans['lv']['TITLE'] : '');
    $sheet->setCellValue($letters[6] . $rowNum, isset($trans['ru']['TITLE']) ? $trans['ru']['TITLE'] : '');
    $sheet->setCellValue($letters[7] . $rowNum, isset($trans['en']['SHORT_DESCR']) ? $trans['en']['SHORT_DESCR'] : '');
    $sheet->setCellValue($letters[8] . $rowNum, isset($trans['lv']['SHORT_DESCR']) ? $trans['lv']['SHORT_DESCR'] : '');
    $sheet->setCellValue($letters[9] . $rowNum, isset($trans['ru']['SHORT_DESCR']) ? $trans['ru']['SHORT_DESCR'] : '');
    $sheet->setCellValue($letters[10] . $rowNum, isset($trans['en']['DESCR']) ? $trans['en']['DESCR'] : '');
    $sheet->setCellValue($letters[11] . $rowNum, isset($trans['lv']['DESCR']) ? $trans['lv']['DESCR'] : '');
    $sheet->setCellValue($letters[12] . $rowNum, isset($trans['ru']['DESCR']) ? $trans['ru']['DESCR'] : '');

    $sheet->setCellValue($letters[13] . $rowNum, $row['STANDART_PRICE']);

    $rowNum++;
}

var_dump($rowNum);


$writer = new Xlsx($spreadsheet);
$writer->save(APP_DIR . '/_files/export/products.xlsx');