<?php 

namespace products;

class Product extends \_class\AbstractClass
{

    public function getProductBySlug($slug)
    {
        $result = $this->db->query("SELECT * FROM shop_products WHERE NEW_SKU = ? AND STATUS = 2 AND DELETED = 0", $slug)->fetchArray();
        if($result['ITEM_ID']){
            return $result['ITEM_ID'];
        }

        return false;
        // return $slug;
    }

    public function getProductPrice($itemId)
    {
        $result = $this->db->query("SELECT *
        FROM shop_products_prices
        WHERE ITEM_ID = ?", $itemId)->fetchArray();

        $price = 0.00;
        $price = $result['STANDART_PRICE_WITH_VAT'];
        return $price;
    }
    
    public function getProductPriceWithoutVat($itemId)
    {
        $result = $this->db->query("SELECT *
        FROM shop_products_prices
        WHERE ITEM_ID = ?", $itemId)->fetchArray();

        $price = 0.00;
        $price = $result['STANDART_PRICE'];
        return $price;
    }
    
    public function getProductDiscountPrice($itemId)
    {
        $result = $this->db->query("SELECT *
        FROM shop_products_prices
        WHERE ITEM_ID = ?", $itemId)->fetchArray();

        $price = 0.00;
        $price = $result['DISCOUNT_PRICE_WITH_VAT'];
        return $price;
    }
    
    public function getProductDiscountPriceWithoutVat($itemId)
    {
        $result = $this->db->query("SELECT *
        FROM shop_products_prices
        WHERE ITEM_ID = ?", $itemId)->fetchArray();

        $price = 0.00;
        $price = $result['DISCOUNT_PRICE'];
        return $price;
    }

    public function getProductFullInfo($itemIds)
    {
        if(!$itemIds) return [];

        $qty = [];

        foreach($itemIds as $itemId => $itemQty) {
            $qty[$itemId] = $itemQty;
        }

        $itemIds = implode(', ', array_keys($itemIds));

        $products = $this->db->query("SELECT *
        FROM shop_products
        INNER JOIN shop_products_lang USING (ITEM_ID)
        INNER JOIN shop_products_flags USING (ITEM_ID)
        WHERE ITEM_ID IN ({$itemIds}) AND LANG = ? AND STATUS = 2 AND DELETED = 0", $this->lang)->fetchAll();

        $data = [];

        foreach($products as $product){
            $price = $this->getProductPrice($product['ITEM_ID']);
            $discountPrice = $this->getProductDiscountPrice($product['ITEM_ID']);

            $categoryTitle = '';

            $category = $this->getProductCategories($product['ITEM_ID']);
            if($category){
                $category = $category[0];
                if($category){
                    $categoryClass = new \category\Category;
                    $category = $categoryClass->getCategoryById($category['CATEGORY_ID']);
                    $categoryTitle = $category['TITLE'];
                }
            }

            $data[] = [
                'item_id' => $product['ITEM_ID'],
                'item_name' => $product['TITLE'],
                'item_brand' => "Truckmaster",
                'item_category' => $categoryTitle,
                'price' => $price,
                'quantity' => $qty[$product['ITEM_ID']] ? $qty[$product['ITEM_ID']] : 0,
            ];
        }

        return $data;
    }

    public function getSingleProductView($itemId)
    {
        $this->tpl->define(['single_product' => '/products/tpl/single_product.html']);
        $this->tpl->split_template('single_product', 'SINGLE_PRODUCT');

        $product = $this->db->query("SELECT *
        FROM shop_products
        INNER JOIN shop_products_lang USING (ITEM_ID)
        INNER JOIN shop_products_flags USING (ITEM_ID)
        WHERE ITEM_ID = ? AND LANG = ? AND STATUS = 2 AND DELETED = 0", $itemId, $this->lang)->fetchArray();

        if(!$product){
            $error = new \_class\Error;
            $error->show404();
            return false;
        }

        $category = $this->getProductCategories($product['ITEM_ID']);
        if($category){
            $category = $category[0];
            if($category){
                $category = $category['CATEGORY_ID'];
            }
        } else {
            $this->response->redirect($this->tpl->urlFor('index'));
            die;
        }

        if($category){
            $categoryClass = new \category\Category;
            $category = $categoryClass->getCategoryById($category);
            if($category){
                $this->tpl->assign("CATEGORY_TITLE_BREADCRUMBS", $category['TITLE']);
                $this->tpl->assign("CATEGORY_URL_BREADCRUMBS", $this->tpl->urlFor('category/index', ['slug' => $categoryClass->getUrl($category['CATEGORY_ID'])]));
            } else {
                $this->response->redirect($this->tpl->urlFor('index'));
                die;
            }
        }

        $shortDescr = strip_tags($product['SHORT_DESCR']);
        $shortDescr = htmlentities($shortDescr, ENT_XML1);

        $this->document->setMetaTitle("TruckMaster | " . $product['NEW_SKU']);
        $this->document->setMetaDescription($product['NEW_SKU'] . " - " . $shortDescr);
        $this->document->setMetaKeywords(implode(', ', [$product['NEW_SKU'], $product['SKU'], 'Truckmaster']));
        
        $relatedProducts = $this->db->query("SELECT * 
        FROM shop_products_relation 
        INNER JOIN shop_products ON (shop_products_relation.RELATED_ITEM_ID = shop_products.ITEM_ID)
        WHERE shop_products_relation.ITEM_ID = ? AND STATUS = 2 AND DELETED = 0", $product['ITEM_ID'])->fetchAll();

        $this->tpl->assign("HAS_RELATED", 0);
        if($relatedProducts){
            $this->tpl->assign("HAS_RELATED", 1);
            $productView = new \products\view\ProductView;
            foreach($relatedProducts as $related){
                $productView->getProductCard($related['RELATED_ITEM_ID'], "RELATED_PRODUCTS");
            }
        }

        $price = $this->getProductPrice($itemId);
        $this->tpl->assign("PRODUCT_PRICE", $price);
        
        $discountPrice = $this->getProductDiscountPrice($itemId);
        $this->tpl->assign("DISCOUNT_PRICE", $discountPrice);

        $reservedCount = $this->db->query("SELECT SUM(QTY) as QTY FROM tbl_order_product_lock WHERE ITEM_ID = ?", $product['ITEM_ID'])->fetchArray();
        $reservedCount = $reservedCount['QTY'] ?: 0;
        $available = $product['STOCK'] - $reservedCount;
        $this->tpl->assign("AVAILABLE", $available);

        $this->tpl->assign_array($product);

        $this->tpl->assign("DESCR", nl2br($product['DESCR']));

    
        $images = new \products\ProductImages;
        $imageArray = $images->getImages($product['ITEM_ID']);
        
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


        $productAttributes = $this->db->query("SELECT * 
        FROM shop_products_attributes 
        INNER JOIN shop_attributes_lang USING (ATTR_ID)
        INNER JOIN shop_attributes_values USING (VAL_ID)
        WHERE ITEM_ID = ? AND LANG = ?", $product['ITEM_ID'], $this->lang)->fetchAll();

        if($productAttributes){
            $this->tpl->assign("HAS_ATTRIBUTES", true);
        }
        
        foreach($productAttributes as $prodAttr){
            $this->tpl->assign_array($prodAttr);
            $this->tpl->parse("PRODUCT_ATTRIBUTES", ".product_attributes");
        }

        $files = $this->db->query("SELECT * FROM shop_products_files WHERE ITEM_ID = ? AND FILE_TYPE = 2", $itemId)->fetchAll();
        foreach($files as $file){
            $this->tpl->assign_array($file);
            $this->tpl->assign("FILE_NAME", basename($file['FILE']));
            $this->tpl->parse("PRODUCT_FILES", ".product_files");
        }

        $additionalSku = $this->db->query("SELECT * FROM shop_products_sku WHERE ITEM_ID = ?", $itemId)->fetchAll();
        if(count($additionalSku)){
            $this->tpl->assign("HAS_ADDITIONAL_SKU", true);
            foreach($additionalSku as $row){
                $this->tpl->assign("ADDITIONAL_SKU", $row['SKU']);
                $this->tpl->parse("ADDITIONAL_SKU_ROW", ".additional_sku_row");
            }
        }

        $analogs = $this->db->query("SELECT * FROM shop_products_analogs WHERE ITEM_ID = ?", $itemId)->fetchAll();
        if(count($analogs)){
            $this->tpl->assign("HAS_ANALOGS", true);
            foreach($analogs as $row){
                $this->tpl->assign_array($row);

                $this->tpl->parse("ANALOG_ROW", ".analog_row");
            }
        }

        $filterSizes = $this->db->query("SELECT * FROM shop_products_filter_sizes WHERE ITEM_ID = ?", $itemId)->fetchArray();
        if($filterSizes){
            $this->tpl->assign("HAS_FILTER_SIZES", true);
            $this->tpl->assign_array($filterSizes);
        }

        $this->db->query("INSERT INTO shop_products_view (ITEM_ID, UID, SESSION_ID, CREATE_DATE, IP) VALUES (?, ?, ?, now(), ?)", $product['ITEM_ID'], $this->user->uid, session_id(), $this->request->server['REMOTE_ADDR']);
        $this->tpl->assign("ADMIN_MENU_ITEM_ID", $product['ITEM_ID']);
        $this->tpl->parse("SINGLE_VIEW", "single_view");
    }

    public function getProductCategories($itemId)
    {
        $result = $this->db->query("SELECT * FROM shop_products_category WHERE ITEM_ID = ?", $itemId)->fetchAll();
        return $result;
    }


}