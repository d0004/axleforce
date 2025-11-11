<?php

namespace products;

class ProductCart extends \_class\AbstractClass
{

    public $itemCount = 0;
    public $totalPrice = 0.00;
    public $totalPriceWithVat = 0.00;
    public $cartProducts = [];
    public $productIds = [];

    function __construct()
    {
        parent::__construct();

        if($this->user->uid){
            $this->assignToUid();
        }

        $this->loadCartInfo();
        $this->getProductIds();
    }

    protected function getProductIds()
    {
        $this->productIds = array_reduce($this->cartProducts, function($carry, $item) {
            $carry[] = $item['ITEM_ID'];
            return $carry;
        }, []);
    }

    public function getCartCategories()
    {
        if(count($this->productIds) <= 0) return false;

        $itemIds = implode(", ", $this->productIds);

        $result = $this->db->query("SELECT CATEGORY_ID, SHIPPABLE, STATUS, DELETED
        FROM shop_products_category 
        INNER JOIN shop_categories USING (CATEGORY_ID)
        WHERE ITEM_ID IN (" . $itemIds . ")")->fetchAll();

        if(!$result) return false;

        return array_reduce($result, function($carry, $item) {
            $carry[$item['CATEGORY_ID']] = $item;
            return $carry;
        }, []);
    }

    protected function assignToUid()
    {
        if($this->user->uid){
            $result = $this->db->query("SELECT * FROM cart WHERE (UID = ? AND PHPSESSID = ?)", 0, session_id())->fetchAll();
            foreach($result as $row){
                $inCart = $this->db->query("SELECT * FROM cart WHERE UID = ? AND ITEM_ID = ?", $this->user->uid, $row['ITEM_ID'])->fetchArray();
                if($inCart){
                    $this->db->query("DELETE FROM cart WHERE ITEM_ID = ? AND PHPSESSID = ? AND UID = 0", $row['ITEM_ID'], session_id());
                } else {
                    $this->db->query("UPDATE IGNORE cart SET UID = ? WHERE UID = 0 AND ITEM_ID = ? AND PHPSESSID = ?", $this->user->uid, $row['ITEM_ID'], session_id());
                }
            }

            $this->db->query("UPDATE IGNORE cart SET PHPSESSID = '' WHERE UID = ?", $this->user->uid);
            $result = $this->db->query("SELECT * FROM cart WHERE UID = ? GROUP BY UID, ITEM_ID HAVING COUNT(*) > 1", $this->user->uid)->fetchAll();
            foreach($result as $row){
                $this->db->query("DELETE FROM cart WHERE ITEM_ID = ? AND PHPSESSID = ? AND UID = ?", $row['ITEM_ID'], $row['SESSION_ID'], $row['UID']);
            }
        }

        return true;
    }

    public function loadCartInfo()
    {
        $productClass = new \products\Product;

        if($this->user->uid){

            // $this->db->query("UPDATE IGNORE cart SET UID = ?, PHPSESSID = '' WHERE PHPSESSID = ? AND UID = 0", $this->user->uid, session_id());
            // $this->db->query("DELETE FROM cart WHERE PHPSESSID = ? AND UID = 0", session_id());

            // $result = $this->db->query("SELECT COUNT(*) AS ITEM_COUNT FROM cart WHERE UID = ?", $this->user->uid)->fetchArray();
            
            $this->cartProducts = $this->db->query("SELECT * 
            FROM cart 
            INNER JOIN shop_products USING (ITEM_ID)
            LEFT JOIN shop_products_lang USING (ITEM_ID)
            -- LEFT JOIN shop_products_category USING (ITEM_ID)
            WHERE UID = ? AND LANG = ?", $this->user->uid, $this->lang)->fetchAll();

        } else {
            // $result = $this->db->query("SELECT COUNT(*) AS ITEM_COUNT FROM cart WHERE PHPSESSID = ?", session_id())->fetchArray();
            
            $this->cartProducts = $this->db->query("SELECT * FROM cart 
            INNER JOIN shop_products USING (ITEM_ID)
            LEFT JOIN shop_products_lang USING (ITEM_ID)
            WHERE PHPSESSID = ? AND LANG = ?", session_id(), $this->lang)->fetchAll();
        }

        $itemCount = 0;

        foreach($this->cartProducts as $k => $product){

            if($product['STATUS'] != 2 || $product['DELETED'] == 1){
                unset($this->cartProducts[$k]);
                continue;
            }

            $productCategory = $this->db->query("SELECT *
            FROM shop_products_category 
            INNER JOIN shop_categories USING (CATEGORY_ID)
            WHERE ITEM_ID = ?", $product['ITEM_ID'])->fetchAll();

            $this->cartProducts[$k]['CATEGORY'] = $productCategory;

            $hasActiveCategory = false;
            foreach($productCategory as $category){
                if($category['STATUS'] == 2 && $category['DELETED'] == 0){
                    $hasActiveCategory = true;
                    break;
                }
            }

            if(!$hasActiveCategory){
                unset($this->cartProducts[$k]);
                continue;
            }

            $itemCount += $product['QTY'];

            $price = $productClass->getProductPriceWithoutVat($product['ITEM_ID']);
            $discountPrice = $productClass->getProductDiscountPriceWithoutVat($product['ITEM_ID']);
            
            $priceWithVat = $productClass->getProductPrice($product['ITEM_ID']);
            $discountPriceWithVat = $productClass->getProductDiscountPrice($product['ITEM_ID']);

            if($discountPrice > 0){
                $totalPrice =  $discountPrice * $product['QTY'];
                $totalPriceWithVat =  $discountPriceWithVat * $product['QTY'];
                $this->cartProducts[$k]['PRICE'] = number_format($discountPrice, 2, '.', '');
                $this->cartProducts[$k]['PRICE_WITH_VAT'] = number_format($discountPriceWithVat, 2, '.', '');
            } else {
                $totalPrice =  $price * $product['QTY'];
                $totalPriceWithVat =  $priceWithVat * $product['QTY'];
                $this->cartProducts[$k]['PRICE'] = number_format($price, 2, '.', '');
                $this->cartProducts[$k]['PRICE_WITH_VAT'] = number_format($priceWithVat, 2, '.', '');
            }
            
            $this->totalPrice += $totalPrice;
            $this->totalPriceWithVat += $totalPriceWithVat;

            $this->cartProducts[$k]['TOTAL_PRICE'] = number_format($totalPrice, 2, '.', '');
            $this->cartProducts[$k]['TOTAL_PRICE_WITH_VAT'] = number_format($totalPriceWithVat, 2, '.', '');
        }

        $this->totalPrice = number_format($this->totalPrice, 2, '.', '');
        $this->totalPriceWithVat = number_format($this->totalPriceWithVat, 2, '.', '');

        $this->itemCount = $itemCount;
        // $this->itemCount = $result['ITEM_COUNT'];
    }

    public function addToCart(\_class\Request $request)
    {

        if(!isset($request->post['itemId']) || !isset($request->post['qty'])){
            return false;
        }

        $options = $this->formatOptions(isset($request->post['options']) ? $request->post['options'] : '');

        if($this->user->uid){
            $this->db->query("INSERT IGNORE INTO cart (UID, PHPSESSID, ITEM_ID, QTY, OPTIONS) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE QTY = ?", 
            $this->user->uid, '', $request->post['itemId'], $request->post['qty'], $options, $request->post['qty']);
        } else {
            $this->db->query("INSERT IGNORE INTO cart (UID, PHPSESSID, ITEM_ID, QTY, OPTIONS) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE QTY = ?", 
            '', session_id(), $request->post['itemId'], $request->post['qty'], $options, $request->post['qty']);
        }
        

        return true;

    }

    protected function formatOptions($options)
    {
        if(!$options){
            return '';
        }

        return $options;
    }

    public function getCartDropdown()
    {
        if(!$this->itemCount){
            return ' ';
        }
        
        $this->tpl->define(['cart' => '/products/tpl/cart.html']);
        $this->tpl->split_template('cart', 'CART');

        $this->tpl->clear_parse("CART_DROPDOWN_ITEM");

        $this->tpl->assign_array([
            "CART_TOTAL_PRICE" => $this->totalPriceWithVat,
        ]);

        foreach($this->cartProducts as $product){

            $imageClass = new \products\ProductImages;
            $image = $imageClass->getMainImage($product['ITEM_ID'], 3);
            
            $this->tpl->assign_array([
                "ITEM_ID" => $product['ITEM_ID'],
                "PRODUCT_NEW_SKU" => $product['NEW_SKU'],
                "PRODUCT_TITLE" => $product['TITLE'],
                "PRODUCT_QTY" => $product['QTY'],
                "PRODUCT_PRICE" => $product['PRICE_WITH_VAT'],
                "IMAGE_LINK" => $image,
            ]);

            $this->tpl->parse("CART_DROPDOWN_ITEM", ".cart_dropdown_item");
        }

        $this->tpl->parse('DROPDOWN_CART_AJAX_HTML', 'cart_dropdown');
        return $this->tpl->fetch('DROPDOWN_CART_AJAX_HTML');

    }
   
    public function getSmallCart()
    {
        
        $this->tpl->define(['cart' => '/products/tpl/cart.html']);
        $this->tpl->split_template('cart', 'CART');

        $this->tpl->assign_array([
            "CART_ITEM_COUNT" => $this->itemCount,
            "CART_TOTAL_PRICE" => $this->totalPriceWithVat,
        ]);

        $this->tpl->parse('SMALL_CART_AJAX_HTML', 'small_cart');
        return $this->tpl->fetch('SMALL_CART_AJAX_HTML');
    }

    

    public function removeFromCart(\_class\Request $request){

        if(!isset($request->post['itemId'])){
            return false;
        }

        if($this->user->uid){
            $this->db->query("DELETE FROM cart WHERE ITEM_ID = ? AND UID = ?", $request->post['itemId'], $this->user->uid);
        } else {
            $this->db->query("DELETE FROM cart WHERE ITEM_ID = ? AND PHPSESSID = ?", $request->post['itemId'], session_id());
        }

        return true;

    }

    public function clearCart()
    {
        
        if($this->user->uid){
            $this->db->query("DELETE FROM cart WHERE UID = ?", $this->user->uid);
        } else {
            $this->db->query("DELETE FROM cart WHERE PHPSESSID = ?", session_id());
        }
        
        return true;
    }

}