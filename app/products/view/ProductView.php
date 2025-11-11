<?php

namespace products\view;

class ProductView extends AbstractClass
{

    public function getProductCard($itemId, $parsePlace)
    {

        $this->tpl->define(['product_view' => '/products/tpl/product_view.html']);
        $this->tpl->split_template('product_view', 'PRODUCT_VIEW');

        $result = $this->db->query("SELECT * 
        FROM shop_products 
        INNER JOIN shop_products_lang USING (ITEM_ID) 
        INNER JOIN shop_products_flags USING (ITEM_ID) 
        WHERE ITEM_ID = ? AND LANG = ? AND STATUS = 2 AND DELETED = 0", $itemId, $this->lang)->fetchArray();

        $reservedCount = $this->db->query("SELECT SUM(QTY) as QTY FROM tbl_order_product_lock WHERE ITEM_ID = ?", $itemId)->fetchArray();
        $reservedCount = $reservedCount['QTY'] ?: 0;
        $available = $result['STOCK'] - $reservedCount;
        $this->tpl->assign("AVAILABLE", $available);

        $this->tpl->assign_array($result);

        $product = new \products\Product;

        $price = $product->getProductPrice($itemId);
        $this->tpl->assign("PRODUCT_PRICE", $price);
        
        $discountPrice = $product->getProductDiscountPrice($itemId);
        $this->tpl->assign("DISCOUNT_PRICE", $discountPrice);

        $images = new \products\ProductImages;
        $mainImage = $images->getMainImage($itemId, 2);
        $this->tpl->assign("MAIN_PRODUCT_IMAGE", $mainImage);

        // $this->tpl->parse('FEATURED_PRODUCTS_CARDS', '.single_card');
        $this->tpl->parse($parsePlace, '.single_card');
        
    }

    public function getCategoryListProductView($itemId, $parsePlace)
    {

        $this->tpl->define(['product_view' => '/products/tpl/product_view.html']);
        $this->tpl->split_template('product_view', 'PRODUCT_VIEW');

        $result = $this->db->query("SELECT * 
        FROM shop_products 
        INNER JOIN shop_products_lang USING (ITEM_ID) 
        INNER JOIN shop_products_flags USING (ITEM_ID) 
        WHERE ITEM_ID = ? AND LANG = ? AND STATUS = 2 AND DELETED = 0", $itemId, $this->lang)->fetchArray();

        $reservedCount = $this->db->query("SELECT SUM(QTY) as QTY FROM tbl_order_product_lock WHERE ITEM_ID = ?", $itemId)->fetchArray();
        $reservedCount = $reservedCount['QTY'] ?: 0;
        $available = $result['STOCK'] - $reservedCount;
        $this->tpl->assign("AVAILABLE", $available);

        $this->tpl->assign_array($result);

        $product = new \products\Product;
        $price = $product->getProductPrice($itemId);
        $this->tpl->assign("PRODUCT_PRICE", $price);

        $discountPrice = $product->getProductDiscountPrice($itemId);
        $this->tpl->assign("DISCOUNT_PRICE", $discountPrice);

        $images = new \products\ProductImages;
        $mainImage = $images->getMainImage($itemId, 2);
        $this->tpl->assign("MAIN_PRODUCT_IMAGE", $mainImage);


        $this->tpl->parse($parsePlace, '.list_single_product_card');

    }

    public function getQuickView($itemId)
    {
        
        $this->tpl->define(['quick_view' => '/products/tpl/quick_view.html']);
        $this->tpl->split_template('quick_view', 'QUICK_VIEW');

        $result = $this->db->query("SELECT * 
        FROM shop_products 
        INNER JOIN shop_products_lang USING (ITEM_ID) 
        INNER JOIN shop_products_flags USING (ITEM_ID) 
        WHERE ITEM_ID = ? AND LANG = ? AND STATUS = 2 AND DELETED = 0", $itemId, $this->lang)->fetchArray();

        $this->tpl->assign_array($result);

        $product = new \products\Product;
        $price = $product->getProductPrice($itemId);
        $this->tpl->assign("PRODUCT_PRICE", $price);

        $discountPrice = $product->getProductDiscountPrice($itemId);
        $this->tpl->assign("DISCOUNT_PRICE", $discountPrice);

        $images = new \products\ProductImages;
        $imageArray = $images->getImages($itemId);
        
        if($imageArray){
            foreach($imageArray as $image){
                $this->tpl->assign_array([
                    "ORIGINAL_LINK" => $image['0'] ? $image['0'] : BLANK_IMAGE,
                    "FILE_LINK" => $image['1'] ? $image['1'] : BLANK_IMAGE,
                ]);
                $this->tpl->parse("BIG_IMAGES", ".big_images");
                
                $this->tpl->assign_array([
                    "FILE_LINK" => $image['3'] ? $image['3'] : BLANK_IMAGE,
                ]);
                $this->tpl->parse("SMALL_IMAGES", ".small_images");
            }
        } else {
            $this->tpl->assign_array([
                "ORIGINAL_LINK" => BLANK_IMAGE,
                "FILE_LINK" => BLANK_IMAGE,
            ]);
            $this->tpl->parse("BIG_IMAGES", ".big_images");

            $this->tpl->assign_array([
                "FILE_LINK" => BLANK_IMAGE,
            ]);
            $this->tpl->parse("SMALL_IMAGES", ".small_images");
        }

        $this->tpl->parse("QUICK_VIEW_RESULT", "quick_view");
        return $this->tpl->fetch("QUICK_VIEW_RESULT");

    }

    public function getLatestProducts()
    {

        $this->tpl->define(['latest_product' => '/products/tpl/latest_product.html']);
        $this->tpl->split_template('latest_product', 'LATEST_PRODUCT');
        
        if($this->user->uid){
            $result = $this->db->query("SELECT DISTINCT(ITEM_ID), CREATE_DATE
            FROM shop_products_view
            WHERE UID = ?
            ORDER BY CREATE_DATE DESC
            LIMIT 10", $this->user->uid)->fetchAll();
        } else {
            $result = $this->db->query("SELECT DISTINCT(ITEM_ID), CREATE_DATE
            FROM shop_products_view
            WHERE SESSION_ID = ?
            ORDER BY CREATE_DATE DESC
            LIMIT 10", session_id())->fetchAll();
        }

        foreach($result as $row){
            $this->getProductCard($row['ITEM_ID'], 'LATEST_PRODUCTS_CARDS');
        }
        
        if($this->tpl->get_assigned("LATEST_PRODUCTS_CARDS")){
            $this->tpl->parse("LATEST_PRODUCTS_RESULT", ".latest_product");
            return $this->tpl->fetch("LATEST_PRODUCTS_RESULT");
        } 

        return false;
    }
}