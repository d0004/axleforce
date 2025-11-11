<?php 

namespace products;

class CategoryImages extends \_class\AbstractClass
{
    public function getImages($itemId)
    {
        $sizes = \_class\Registry::load('imageSizes');
        $data = [];

        $result = $this->db->query("SELECT DISTINCT(FILE_ID) AS FILE_ID FROM shop_products_category_files WHERE CATEGORY_ID = ? AND FILE_TYPE = 1 ORDER BY IS_MAIN DESC, `ORDER`, FILE_ID DESC", $itemId)->fetchAll();

        if($result){
            foreach($result as $file){
                $image = $this->db->query("SELECT * FROM shop_products_category_files WHERE CATEGORY_ID = ? AND FILE_ID = ? AND IMAGE_SIZE = ?", $itemId, $file['FILE_ID'], 0)->fetchArray();
                if($image){
                    $data[$file['FILE_ID']][0] = $image['FILE'];
                }

                foreach($sizes as $size => $v){
                    $image = $this->db->query("SELECT * FROM shop_products_category_files WHERE CATEGORY_ID = ? AND FILE_ID = ? AND IMAGE_SIZE = ?", $itemId, $file['FILE_ID'], $size)->fetchArray();
                    $data[$file['FILE_ID']][$size] = $image['FILE'];
                }                
            }
        }

        return $data;
    }

    public function getMainImage($itemId, $size)
    {
        $sizes = \_class\Registry::load('imageSizes');
        if(!array_key_exists($size, $sizes)){
            $size = 0;
        }

        $image = $this->db->query("SELECT DISTINCT(FILE_ID) FROM shop_products_category_files WHERE CATEGORY_ID = ? AND FILE_TYPE = 1 ORDER BY IS_MAIN DESC, `ORDER`, FILE_ID DESC LIMIT 1;", $itemId)->fetchArray();
        if($image && $image['FILE_ID']){
            $result = $this->db->query("SELECT * FROM shop_products_category_files WHERE CATEGORY_ID = ? AND FILE_ID = ? AND IMAGE_SIZE = ?", $itemId, $image['FILE_ID'], $size)->fetchArray();
            if($result){
                return $result['FILE'];
            } else {
                $result = $this->db->query("SELECT * FROM shop_products_category_files WHERE CATEGORY_ID = ? AND FILE_ID = ? AND IMAGE_SIZE = ?", $itemId, $image['FILE_ID'], 0)->fetchArray();
                if($result){
                    return $result['FILE'];
                }
            }
        } 

        return BLANK_IMAGE;
    }
}