<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['statistics' => '/admin/tpl/statistics.html']);
$tpl->split_template('statistics', 'STATISTICS');

$uids = [];
$tmpUids = $db->query("SELECT UID FROM tbl_user_status WHERE STATUS = 40")->fetchAll();
foreach($tmpUids as $uid){
    $uids[] = $uid['UID'];
}

$uids = implode("', '", $uids);
// Популярные товары

$result = $db->query("SELECT ITEM_ID, SKU, NEW_SKU, COUNT(*) as COU, MAX(shop_products_view.CREATE_DATE) AS LAST_VIEW_DATE
FROM shop_products_view
INNER JOIN shop_products USING (ITEM_ID)
WHERE UID NOT IN ('{$uids}') AND NOW() - INTERVAL 1 MONTH < shop_products_view.CREATE_DATE
GROUP BY ITEM_ID 
ORDER BY COU DESC
LIMIT 10")->fetchAll();

$itemData = [];
foreach($result as $row){

    $date_a = new DateTime();
    $date_b = new DateTime($row['LAST_VIEW_DATE']);
    $interval = date_diff($date_a,$date_b);

    $tpl->assign("TIME_FROM_LAST_VIEW", $interval->format('%d д, %h ч, %i м назад '));

    $row['LAST_VIEW_DATE'] = substr($row['LAST_VIEW_DATE'], 0, -3);
    $tpl->assign_array($row);
    $tpl->parse("POPULAR_PRODUCT_ROW", ".popular_product_row");


    $detailed = $db->query("SELECT ITEM_ID, COUNT(*) AS COU, DATE_FORMAT(CREATE_DATE, '%Y-%m-%d') AS FDATE
    FROM shop_products_view 
    WHERE ITEM_ID = ? AND NOW() - INTERVAL 1 MONTH < shop_products_view.CREATE_DATE
    GROUP BY DATE_FORMAT(CREATE_DATE, '%Y-%m-%d')
    ORDER BY CREATE_DATE ASC", $row['ITEM_ID'])->fetchAll();

    foreach($detailed as $row2){
        $itemData[$row['NEW_SKU']][$row2['FDATE']] = $row2['COU'];
    }
    
    
}

$chartData = [];
$now = new DateTime();
foreach($itemData as $sku => $data){

    $date = new DateTime();
    $date->modify('-1 month');
    $tmp = [];
    while($date->format('Y-m-d') != $now->format('Y-m-d')){
        $tmp[] = $data[$date->format('Y-m-d')] ?? 0;
        $date->modify('+1 day');
    }

    $chartData['data'][] = [
        'name' => $sku,
        'data' => $tmp,
    ];
}

$date = new DateTime();
$date->modify('-1 month');

while($date->format('Y-m-d') != $now->format('Y-m-d')){
    $chartData['dates'][] = $date->format('Y-m-d');
    $date->modify('+1 day');
}


$tpl->assign("CHART_1_JSON", json_encode($chartData));

// Популярные товары

// Последние просмотры
$result2 = $db->query("SELECT ITEM_ID, SKU, NEW_SKU, shop_products_view.CREATE_DATE AS LAST_VIEW_DATE, shop_products_view.UID, FNAME, LNAME
FROM shop_products_view
INNER JOIN shop_products USING (ITEM_ID)
LEFT JOIN tbl_user USING (UID)
WHERE UID NOT IN ('{$uids}') AND NOW() - INTERVAL 1 MONTH < shop_products_view.CREATE_DATE
ORDER BY shop_products_view.CREATE_DATE DESC
LIMIT 10")->fetchAll();

foreach($result2 as $row2){
    
    $date_a = new DateTime();
    $date_b = new DateTime($row2['LAST_VIEW_DATE']);
    $interval = date_diff($date_a,$date_b);

    $tpl->assign("TIME_FROM_LAST_VIEW", $interval->format('%d д, %h ч, %i м назад '));

    $row2['LAST_VIEW_DATE'] = substr($row2['LAST_VIEW_DATE'], 0, -3);
    $tpl->assign_array($row2);
    $tpl->parse("LAST_PRODUCT_ROW", ".last_product_row");
}

// Последние просмотры


// Создание счетов

$result = $db->query("SELECT DATE_FORMAT(CREATE_DATE, '%Y-%m-%d') AS FDATE, COUNT(*) AS COU
FROM tbl_bill 
WHERE UID NOT IN ('{$uids}') AND NOW() - INTERVAL 1 MONTH < CREATE_DATE
GROUP BY DATE_FORMAT(CREATE_DATE, '%Y-%m-%d')
ORDER BY CREATE_DATE ASC")->fetchAll();

$tmp = [];
foreach($result as $row){
    $tmp[$row['FDATE']] = $row['COU'];
}

$chartData = [];
$now = new DateTime();
$date = new DateTime();
$date->modify('-1 month');

$dataTmp = [];
while($date->format('Y-m-d') != $now->format('Y-m-d')){
    $dataTmp[] = $tmp[$date->format('Y-m-d')] ?? 0;
    $chartData['dates'][] = $date->format('Y-m-d');
    $date->modify('+1 day');
}

$chartData['data'][] = [
    'name' => 'Bill created',
    'data' => $dataTmp,
];




$result = $db->query("SELECT DATE_FORMAT(CREATE_DATE, '%Y-%m-%d') AS FDATE, COUNT(*) AS COU
FROM tbl_bill 
WHERE UID NOT IN ('{$uids}') AND NOW() - INTERVAL 1 MONTH < CREATE_DATE AND STATUS = 2
GROUP BY DATE_FORMAT(CREATE_DATE, '%Y-%m-%d')
ORDER BY CREATE_DATE ASC")->fetchAll();

$tmp = [];
foreach($result as $row){
    $tmp[$row['FDATE']] = $row['COU'];
}

$date = new DateTime();
$date->modify('-1 month');

$dataTmp = [];
while($date->format('Y-m-d') != $now->format('Y-m-d')){
    $dataTmp[] = $tmp[$date->format('Y-m-d')] ?? 0;
    $date->modify('+1 day');
}


$chartData['data'][] = [
    'name' => 'Bill paid',
    'data' => $dataTmp,
];

$tpl->assign("CHART_2_JSON", json_encode($chartData));

// Создание счетов






$tpl->parse("CONTENT", "statistics");
include_once('../_body_admin.php');