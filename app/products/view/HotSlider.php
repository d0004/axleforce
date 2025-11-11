<?php

namespace products\view;

class HotSlider extends AbstractClass
{

    public function getView($parseMark, $type = 0)
    {

        $viewClass = new \products\view\ProductView;

        $this->tpl->define(['hot_slider' => '/products/tpl/hot_slider.html']);
        $this->tpl->split_template('hot_slider', 'HOT_SLIDER');

        $result = $this->db->query("SELECT ITEM_ID 
        FROM shop_products
        INNER JOIN shop_products_flags USING (ITEM_ID)
        INNER JOIN shop_products_category USING (ITEM_ID)
        INNER JOIN shop_categories USING (CATEGORY_ID)
        WHERE shop_products.STATUS = 2 AND shop_products.DELETED = 0 AND shop_categories.STATUS = 2 AND shop_categories.DELETED = 0 AND shop_products_flags.HOT = 1 
        ORDER BY HOT_DATE DESC, CREATE_DATE DESC LIMIT 20")->fetchAll();

        if(count($result) <= 0){
            return false;
        }

        $this->tpl->assign("HOT_PRODUCTS_CARDS", "");

        foreach($result as $itemId){
            $viewClass->getProductCard($itemId, 'HOT_PRODUCTS_CARDS');
        }

        $this->tpl->parse($parseMark, 'hot_slider');
    }

    
}