<?php

namespace products\view;

class CategoryProducts extends AbstractClass
{

    protected $category;
    protected $categoryData;
    protected $image = "/assets/images/categories/category-overlay-1.jpg";

    public function setCategory($category)
    {
        $this->category = $category;
    
        $category = $this->db->query("SELECT * 
        FROM shop_categories
        INNER JOIN shop_categories_lang USING (CATEGORY_ID)
        WHERE shop_categories.STATUS = 2 AND shop_categories.DELETED = 0 AND LANG = ? AND CATEGORY_ID = ?", \_class\Registry::load('lang'), $this->category)->fetchArray();

        if(!$category){
            $category = $this->db->query("SELECT * 
            FROM shop_categories
            INNER JOIN shop_categories_lang USING (CATEGORY_ID)
            WHERE shop_categories.STATUS = 2 AND shop_categories.DELETED = 0 AND LANG = ? AND CATEGORY_ID = ?", \_class\Registry::load('defaultLang'), $this->category)->fetchArray();
        }

        if(!$category){
            return false;
        }

        $this->categoryData = $category;
        return true;
    }

    public function getView($parseMark, $type = 0)
    {
        if(!$this->categoryData){
            return false;
        }

        $viewClass = new \products\view\ProductView;

        $this->tpl->clear_parse("CATEGORY_PRODUCT_BLOCK_ITEMS");

        $this->tpl->define(['category_product_view' => '/products/tpl/category_product_view.html']);
        $this->tpl->split_template('category_product_view', 'CATEGORY_PRODUCT_VIEW');

        $result = $this->db->query("SELECT ITEM_ID 
        FROM shop_products
        INNER JOIN shop_products_category USING (ITEM_ID)
        INNER JOIN shop_categories USING (CATEGORY_ID)
        WHERE shop_products.STATUS = 2 AND shop_categories.STATUS = 2 AND shop_products_category.CATEGORY_ID = ? 
        ORDER BY CREATE_DATE DESC LIMIT 20", $this->category)->fetchAll();

        if(count($result) <= 0){
            return false;
        }

        foreach($result as $itemId){
            $viewClass->getProductCard($itemId, 'CATEGORY_PRODUCT_BLOCK_ITEMS');
        }

        $category = new \category\Category;

        $this->tpl->assign_array([
            "CATEGORY_TITLE" => $this->categoryData['TITLE'],
            "CATEGORY_LINK" => $category->getUrl($this->category),
            "CATEGORY_IMAGE" => $this->image,
        ]);

        $this->tpl->parse($parseMark, 'category_product_view');
    }

    public function setImage($image)
    {
        $this->image = $image;
    }
    
}