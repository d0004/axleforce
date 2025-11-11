<?php 

chdir(__DIR__);
include_once("./../_main_exe.php");

use \Gumlet\ImageResize;

ini_set('memory_limit', '300M');
ini_set('max_execution_time', '300');

$file = PUBLIC_DIR . '/files_public/product_images/LAMPY TYLNE 2/FT-500 led/FT-500-135 LED/FT-500-135LED-nr2-0.jpg';

$ext = pathinfo($file, PATHINFO_EXTENSION);
$fileName = str_replace(" ", "", basename($file, "." . $ext));

$directory = dirname($file);
$newDirectory = str_replace("product_images_original", "product_images", $directory);
if(!is_dir($newDirectory)){
    if (!@mkdir($newDirectory, 0777, true)) {
        echo "Не удалось создать директории | {$newDirectory}<br>\n";
        die;
    }
}

$pathParts = explode("/", $file);
$pathParts = array_reverse($pathParts);

$publicPath = [];
foreach($pathParts as $i => $part){
    if($i == 0) continue;
    if($part == "server") break;
    if($part == "product_images_original") $part = "product_images";
    $publicPath[] = $part;
}

$publicPath = array_reverse($publicPath);
$publicPath = '/' . implode("/", $publicPath);


$imageResizer = new \products\ImageResizer;

$name = $fileName . '-0.' . $ext;
$newFileName = $newDirectory . '/' . $name;
$imageResizer->resizeWithFill($file, $newFileName, $data['w'], $data['h'], 85);

foreach($imageSizes as $size => $data){
    $name = $fileName . '-' . $size . '.' . $ext;
    $newFileName = $newDirectory . '/' . $name;
    $imageResizer->resizeWithFill($file, $newFileName, $data['w'], $data['h'], 85);
}



