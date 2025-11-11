#!/usr/local/bin/php -q
<?php 

chdir(__DIR__);
include_once("./../_main_exe.php");

use \Gumlet\ImageResize;


$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/var/www/shop2/server/files_public/product_images_original'));
$files = array(); 
foreach ($rii as $file) {
    if ($file->isDir()){ 
        continue;
    }
    $files[] = $file->getPathname(); 
}

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

    $newDirectory = str_replace("product_images_original", "product_images", $directory);
    if(!is_dir($newDirectory)){
        if (!@mkdir($newDirectory, 0777, true)) {
            echo "Не удалось создать директории | {$newDirectory}<br>\n";
            continue;
        }
    }

    $sku = str_replace(" ", "", $pathParts['1']);
    $result = $db->query("SELECT * FROM shop_products WHERE SKU = ?", $sku)->fetchArray();
    if(!$result){
        echo "Product not found | " . $sku . "<br>\n";
        continue;
    }

    if(!in_array($result['ITEM_ID'], $deletedImages)){
        $db->query("DELETE FROM shop_products_files WHERE ITEM_ID = ?", $result['ITEM_ID']);
        $deletedImages[] = $result['ITEM_ID'];
    }

    $publicPath = [];
    foreach($pathParts as $i => $part){
        if($i == 0) continue;
        if($part == "server") break;
        if($part == "product_images_original") $part = "product_images";
        $publicPath[] = $part;
    }

    $publicPath = array_reverse($publicPath);
    $publicPath = '/' . implode("/", $publicPath);
    
    $fileName = str_replace(" ", "", basename($file, "." . $ext));

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
