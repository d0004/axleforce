<?php

namespace products\view;

class SalesBlock extends AbstractClass
{

    public function getView($parseMark, $type = 0)
    {

        $viewClass = new \products\view\ProductView;

        $this->tpl->define(['special_deals' => '/products/tpl/special_deals.html']);
        $this->tpl->split_template('special_deals', 'SPECIAL_DEALS');

        $result = $this->db->query("SELECT ITEM_ID 
        FROM shop_products
        INNER JOIN shop_products_flags USING (ITEM_ID)
        INNER JOIN shop_products_category USING (ITEM_ID)
        INNER JOIN shop_categories USING (CATEGORY_ID)
        WHERE shop_products.STATUS = 2 AND shop_products.DELETED = 0 AND shop_categories.STATUS = 2 AND shop_categories.DELETED = 0 AND shop_products_flags.SALE = 1 
        ORDER BY SALE_DATE DESC, CREATE_DATE DESC LIMIT 20")->fetchAll();

        if(count($result) <= 0){
            return false;
        }

        foreach($result as $itemId){
            $viewClass->getProductCard($itemId, 'SALE_PRODUCTS_BLOCK');
        }

        $this->tpl->parse($parseMark, 'special_deals');
    }

    
}