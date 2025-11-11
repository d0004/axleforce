
<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['image_import' => '/admin/tpl/image_import.html']);
$tpl->split_template('image_import', 'IMAGE_IMPORT');

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PUBLIC_DIR . '/files_public/product_images_original'));
$files = array(); 
foreach ($rii as $file) {
    if ($file->isDir()){ 
        continue;
    }
    $files[] = $file->getPathname(); 
}

$skuArr = [];

foreach($files as $file){
    $pathParts = explode("/", $file);
    $pathParts = array_reverse($pathParts);
    $sku = str_replace(" ", "", $pathParts['1']);
    $result = $db->query("SELECT * 
    FROM shop_products 
    LEFT JOIN tmp_upload_image_success USING (SKU)
    WHERE (shop_products.SKU = ? OR shop_products.NEW_SKU = ?) AND tmp_upload_image_success.SKU IS NULL", $sku, $sku)->fetchArray();
    if(!$result){
        continue;        
    }

    $skuArr[$sku][] = $file;
}

foreach($skuArr as $sku => $data){
    $tpl->assign_array([
        "SKU" => $sku,
    ]);
    $tpl->parse("PRODUCT_ROW", ".product_row");
}



// =====================================================


$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PUBLIC_DIR . '/files_public/product_images_original'));
$files = array(); 
foreach ($rii as $file) {
    if ($file->isDir()){ 
        continue;
    }
    $files[] = $file->getPathname(); 
}


$itemArr = [];
$count = 0;

foreach($files as $file){

    $fileName = basename($file);

    $sku = preg_replace('/([\s]{1}[-][\s]{1}nr[\s]{1}[\d]+.jpg|[\s]{1}[-][\s]{1}[\d]+.jpg|[\s]nr[\s][\d]+.jpg)/m', '.jpg', $fileName);
    $sku = preg_replace('/LP[\s][\d]+[\s]-[\s]/m', '', $sku);
    $sku = preg_replace('/[\s]QS.jpg/m', '.jpg', $sku);
    
    $sku = str_replace(" ", "", $sku);
    $sku = str_replace(".jpg", "", $sku);
    $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", $sku)->fetchArray();
    if($result){

        $result2 = $db->query("SELECT * FROM shop_products_files WHERE ITEM_ID = ?", $result['ITEM_ID'])->fetchArray();
        if($result2){
            continue;
        }

        $result2 = $db->query("SELECT * FROM tmp_upload_image_file_success WHERE SKU = ?", $file)->fetchArray();
        if($result2){
            continue;
        }
    } else {
        continue;
    }


    $tpl->assign_array([
        "FILE" => $file,
        "SKU" => $sku,
        "ITEM_ID" => $result['ITEM_ID'],
    ]);
    $tpl->parse("FILE_ROW", ".file_row");

    $count++;
    $itemArr[$result['ITEM_ID']] = true;
   
    // if($count > 1){
    //     break;
    // }
}

$tpl->assign("FILE_COUNT", $count);
$tpl->assign("ITEM_COUNT", count($itemArr));

$tpl->parse("CONTENT", "image_import");
include_once('../_body_admin.php');