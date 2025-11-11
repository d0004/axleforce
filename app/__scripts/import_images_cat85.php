#!/usr/local/bin/php -q
<?php 

die;
die;
die;
die;
die;
die;
die;

chdir(__DIR__);
include_once("./../_main_exe.php");

use \Gumlet\ImageResize;


$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/images_connectors'));
$files = array(); 
foreach ($rii as $file) {
    if ($file->isDir()){ 
        continue;
    }
    $files[] = $file->getPathname(); 
}

// echo '<pre>' . print_r($files, 2) . '</pre>'; die;


// die;


$deletedImages = [];

foreach($files as $file){

    $fileId = 0;
    $result = $db->query("SELECT MAX(FILE_ID) AS FILE_ID FROM shop_products_files")->fetchArray();
    if($result){
        $fileId = $result['FILE_ID'] + 1;
    }

    $directory = dirname($file);
    $ext = pathinfo($file, PATHINFO_EXTENSION);

    $pathParts = explode("/", $file);
    $pathParts = array_reverse($pathParts);

    // echo '<pre>' . print_r($pathParts, 2) . '</pre>';

    $newDirectory = PUBLIC_DIR . '/files_public/category_85';
    if(!is_dir($newDirectory)){
        if (!@mkdir($newDirectory, 0777, true)) {
            echo "Не удалось создать директории | {$newDirectory}<br>\n";
            continue;
        }
    }

    $sku = str_replace(" ", "", $pathParts['0']);

    if (!preg_match('/^(\d+)(?=\.)/', $sku, $matches)) {
        continue;
    }

    $sku = $matches[0];
    $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", $sku)->fetchArray();
    if(!$result){
        echo "Product not found | " . $sku . "<br>\n";
        continue;
    }

    // echo "success \n";
    // continue;

    if(!in_array($result['ITEM_ID'], $deletedImages)){
        $db->query("DELETE FROM shop_products_files WHERE ITEM_ID = ?", $result['ITEM_ID']);
        $deletedImages[] = $result['ITEM_ID'];
    }

    // $publicPath = [];
    // foreach($pathParts as $i => $part){
    //     if($i == 0) continue;
    //     if($part == "server") break;
    //     if($part == "product_images_original") $part = "product_images";
    //     $publicPath[] = $part;
    // }

    // $publicPath = array_reverse($publicPath);
    // $publicPath = '/' . implode("/", $publicPath);
    
    $publicPath = '/files_public/category_85';
    // $publicPath = $newDirectory;

    $fileName = str_replace(" ", "", basename($file, "." . $ext));

    // $name = $fileName . '-0.' . $ext;
    // echo $publicPath . '/' . $name . "\n";
    // continue;


    try {
        $image = new ImageResize($file);
        $image->quality_jpg = 85;
        $image->gamma(false);
        $name = $fileName . '-0.' . $ext;
        $image->save($newDirectory . '/' . $name);

        $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, 0)", $fileId, $result['ITEM_ID'], $publicPath . '/' . $name);
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

            $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, ?)", $fileId, $result['ITEM_ID'], $publicPath . '/' . $name, $size);
        } catch (\Exception $e){
            echo "Error | " . $e->getMessage() . " | " . $file . "<br>\n";
        }
    }

}
