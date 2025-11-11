<?php

namespace products\view;

class FeaturedSlider extends AbstractClass
{

    public function getView($parseMark, $type = 0)
    {

        $viewClass = new \products\view\ProductView;

        $this->tpl->define(['featured_slider' => '/products/tpl/featured_slider.html']);
        $this->tpl->split_template('featured_slider', 'FEATURED_SLIDER');

        $result = $this->db->query("SELECT ITEM_ID 
        FROM shop_products
        INNER JOIN shop_products_flags USING (ITEM_ID)
        INNER JOIN shop_products_category USING (ITEM_ID)
        INNER JOIN shop_categories USING (CATEGORY_ID)
        WHERE shop_products.STATUS = 2 AND shop_products.DELETED = 0 AND shop_categories.STATUS = 2 AND shop_categories.DELETED = 0 AND shop_products_flags.FEATURED = 1 
        ORDER BY FEATURED_DATE DESC, CREATE_DATE DESC LIMIT 20")->fetchAll();

        if(count($result) <= 0){
            return false;
        }

        $this->tpl->assign("FEATURED_PRODUCTS_CARDS", "");

        foreach($result as $itemId){
            $viewClass->getProductCard($itemId, 'FEATURED_PRODUCTS_CARDS');
        }

        $this->tpl->parse($parseMark, 'featured_slider');
    }

    
}