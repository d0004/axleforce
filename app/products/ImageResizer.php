<?php

namespace products;

class ImageResizer extends \_class\AbstractClass
{

    public function uploadAndAddToProduct($path, $itemId)
    {

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        $fileId = 0;
        $result = $this->db->query("SELECT MAX(FILE_ID) AS FILE_ID FROM shop_products_files")->fetchArray();
        if($result){
            $fileId = $result['FILE_ID'] + 1;
        }

        $savePath = FILE_PUBLIC_PATH . 'product_images_2/' . $itemId;
        if(!is_dir($savePath)){
            if (!@mkdir($savePath, 0777, true)) {
                return false;
            }
        }
        
        $fileName = str_replace(" ", "", basename($path, "." . $ext));
        $publicPath = "/files_public/product_images_2/" . $itemId;

        $originalInfo = getimagesize($path);

        $name = $fileName . '-0.' . $ext;
        $newFileName = $savePath . '/' . $name;
        if($this->resizeWithFill($path, $newFileName, $originalInfo['0'], $originalInfo['1'], 85)){
            $this->db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, 0)", $fileId, $itemId, $publicPath . '/' . $name);
        }
        
        foreach(\_class\Registry::load('imageSizes') as $size => $data){
            $name = $fileName . '-' . $size . '.' . $ext;
            $newFileName = $savePath . '/' . $name;

            if($this->resizeWithFill($path, $newFileName, $data['w'], $data['h'], 85)){
                $this->db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, ?)", $fileId, $itemId, $publicPath . '/' . $name, $size);
            }
        }

        return true;

    }
    
    public function uploadAndAddToCategory($path, $categoryId)
    {

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        $fileId = 0;
        $result = $this->db->query("SELECT MAX(FILE_ID) AS FILE_ID FROM shop_products_category_files")->fetchArray();
        if($result){
            $fileId = $result['FILE_ID'] + 1;
        }

        $savePath = FILE_PUBLIC_PATH . 'product_category_images_2/' . $categoryId;
        if(!is_dir($savePath)){
            if (!@mkdir($savePath, 0777, true)) {
                return false;
            }
        }
        
        $fileName = str_replace(" ", "", basename($path, "." . $ext));
        $publicPath = "/files_public/product_category_images_2/" . $categoryId;

        $originalInfo = getimagesize($path);

        $name = $fileName . '-0.' . $ext;
        $newFileName = $savePath . '/' . $name;

        if($this->resizeWithFill($path, $newFileName, $originalInfo['0'], $originalInfo['1'], 85)){
            $this->db->query("INSERT INTO shop_products_category_files (FILE_ID, CATEGORY_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, 0)", $fileId, $categoryId, $publicPath . '/' . $name);
        }
        
        foreach(\_class\Registry::load('imageSizes') as $size => $data){
            $name = $fileName . '-' . $size . '.' . $ext;
            $newFileName = $savePath . '/' . $name;

            if($this->resizeWithFill($path, $newFileName, $data['w'], $data['h'], 85)){
                $this->db->query("INSERT INTO shop_products_category_files (FILE_ID, CATEGORY_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, ?)", $fileId, $categoryId, $publicPath . '/' . $name, $size);
            }
        }

        // try {
        //     $image = new \Gumlet\ImageResize($path);
        //     $image->quality_jpg = 85;
        //     $image->gamma(false); 
        //     $name = $fileName . '-0.' . $ext;
        //     $image->save($savePath . '/' . $name);
    
        //     $this->db->query("INSERT INTO shop_products_category_files (FILE_ID, CATEGORY_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, 0)", $fileId, $categoryId, $publicPath . '/' . $name);
        // } catch (\Exception $e){
        //     return false;
        // }

        // foreach(\_class\Registry::load('imageSizes') as $size => $data){
        //     try {
        //         $image = new \Gumlet\ImageResize($path);
        //         $image->quality_jpg = 85;
        //         $image->gamma(false);
        //         $image->resizeToLongSide($data['w']);
        //         $image->crop($data['w'], $data['h'], true, \Gumlet\ImageResize::CROPCENTER);
        //         $name = $fileName . '-' . $size . '.' . $ext;
        //         $image->save($savePath . '/' . $name);
    
        //         $this->db->query("INSERT INTO shop_products_category_files (FILE_ID, CATEGORY_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, ?)", $fileId, $categoryId, $publicPath . '/' . $name, $size);
        //     } catch (\Exception $e){
        //         return false;
        //     }
        // }

        return true;

    }

    public function resizeWithFill($source_image, $destination, $tn_w, $tn_h, $quality = 100, $wmsource = false)
    {
        $info = getimagesize($source_image);
        $imgtype = image_type_to_mime_type($info[2]);

        switch ($imgtype) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($source_image);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($source_image);
                break;
            case 'image/png':
                $source = imagecreatefrompng($source_image);
                break;
            default:
                return false;
                // die('Invalid image type.');
        }

        #Figure out the dimensions of the image and the dimensions of the desired thumbnail
        $src_w = imagesx($source);
        $src_h = imagesy($source);

        #Do some math to figure out which way we'll need to crop the image
        #to get it proportional to the new size, then crop or adjust as needed

        $x_ratio = $tn_w / $src_w;
        $y_ratio = $tn_h / $src_h;

        if (($src_w <= $tn_w) && ($src_h <= $tn_h)) {
            $new_w = $src_w;
            $new_h = $src_h;
        } elseif (($x_ratio * $src_h) < $tn_h) {
            $new_h = ceil($x_ratio * $src_h);
            $new_w = $tn_w;
        } else {
            $new_w = ceil($y_ratio * $src_w);
            $new_h = $tn_h;
        }

        ini_set('memory_limit', '1000M');
        ini_set('max_execution_time', '300');

        $newpic = imagecreatetruecolor(round($new_w), round($new_h));
        imagecopyresampled($newpic, $source, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
        $final = imagecreatetruecolor($tn_w, $tn_h);
        $backgroundColor = imagecolorallocate($final, 255, 255, 255);
        imagefill($final, 0, 0, $backgroundColor);
        //imagecopyresampled($final, $newpic, 0, 0, ($x_mid - ($tn_w / 2)), ($y_mid - ($tn_h / 2)), $tn_w, $tn_h, $tn_w, $tn_h);
        imagecopy($final, $newpic, (($tn_w - $new_w)/ 2), (($tn_h - $new_h) / 2), 0, 0, $new_w, $new_h);

        #if we need to add a watermark
        if ($wmsource) {
            #find out what type of image the watermark is
            $info    = getimagesize($wmsource);
            $imgtype = image_type_to_mime_type($info[2]);

            #assuming the mime type is correct
            switch ($imgtype) {
                case 'image/jpeg':
                    $watermark = imagecreatefromjpeg($wmsource);
                    break;
                case 'image/gif':
                    $watermark = imagecreatefromgif($wmsource);
                    break;
                case 'image/png':
                    $watermark = imagecreatefrompng($wmsource);
                    break;
                default:
                    die('Invalid watermark type.');
            }

            #if we're adding a watermark, figure out the size of the watermark
            #and then place the watermark image on the bottom right of the image
            $wm_w = imagesx($watermark);
            $wm_h = imagesy($watermark);
            imagecopy($final, $watermark, $tn_w - $wm_w, $tn_h - $wm_h, 0, 0, $tn_w, $tn_h);

        }
        if (imagejpeg($final, $destination, $quality)) {
            return true;
        }
        return false;
    }


}