<?php

namespace category;

class Category extends \_class\AbstractClass
{

    protected $categoryId = 0;
    protected $limit = 16;
    protected $sortPrice = '';
    protected $page = 1;
    protected $shownCount = 0;
    protected $totalCount = 0;
    protected $limitOptions = [16, 32, 64, 128];
    protected $sortPriceOptions = ['' => '---', 'ASC' => 'Price &#8593;', 'DESC' => 'Price &#8595;'];
    protected $filter = [];

    public function getCategoryBySlug($slug)
    {

        $parts = explode('/', $slug);
        $categoryId = 0;

        foreach($parts as $part){
            $category = $this->db->query("SELECT * 
            FROM shop_categories_lang 
            INNER JOIN shop_categories USING (CATEGORY_ID)
            WHERE LANG = ? AND SLUG = ? AND PARENT_ID = ?", $this->lang, $part, $categoryId)->fetchArray();

            if(!$category){
                return false;
            }

            $categoryId = $category['CATEGORY_ID'];
        }

        // var_dump($categoryId);

        // echo '<pre>'; print_r($parts); echo '</pre>'; die;

        return $categoryId;
    }

    protected function createPath($categoryId, &$urlArray = [])
    {
        $category = $this->db->query("SELECT * 
        FROM shop_categories_lang 
        INNER JOIN shop_categories USING (CATEGORY_ID)
        WHERE LANG = ? AND CATEGORY_ID = ?", $this->lang, $categoryId)->fetchArray();
        if(!$category){
            return false;
        }

        if($category['PARENT_ID']){
            $this->createPath($category['PARENT_ID'], $urlArray); 
            $urlArray[] = $category['SLUG'];
        } else {
            $urlArray[] = $category['SLUG'];
        }

        return $urlArray;
    }
    
    protected function createPathByLang($categoryId, $lang, &$urlArray = [])
    {
        $category = $this->db->query("SELECT * 
        FROM shop_categories_lang 
        INNER JOIN shop_categories USING (CATEGORY_ID)
        WHERE LANG = ? AND CATEGORY_ID = ?", $lang, $categoryId)->fetchArray();
        if(!$category){
            return false;
        }

        if($category['PARENT_ID']){
            $this->createPathByLang($category['PARENT_ID'], $lang, $urlArray); 
            $urlArray[] = $category['SLUG'];
        } else {
            $urlArray[] = $category['SLUG'];
        }

        return $urlArray;
    }

    public function getUrl($categoryId)
    {
        $path = $this->createPath($categoryId);
        $result = implode("/", $path);
        return $result;
    }
    
    public function getUrlByLang($categoryId, $lang)
    {
        $path = $this->createPathByLang($categoryId, $lang);
        $result = implode("/", $path);
        return $result;
    }

    public function setCategory($categoryId)
    {
        $this->categoryId = $categoryId;
    }
    
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }
    
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    public function setPage($page)
    {
        $this->page = $page;
    }
    
    public function setSortPrice($sortPrice)
    {
        if(in_array($sortPrice, ['ASC', 'DESC'])){
            $this->sortPrice = $sortPrice;
        }
    }

    public function getCategoriesByParent($parentId)
    {
        return $this->db->query("SELECT *, CATEGORY_ID AS CID, (SELECT COUNT(*) FROM shop_categories WHERE PARENT_ID = CID AND DELETED = 0 AND STATUS = 2) AS CHILD_COUNT
        FROM shop_categories
        INNER JOIN shop_categories_lang USING (CATEGORY_ID)
        WHERE STATUS = 2 AND DELETED = 0 AND LANG = ? AND PARENT_ID = ?
        ORDER BY WEIGHT DESC, CATEGORY_ID ASC", $this->lang, $parentId)->fetchAll();
    }

    public function getCategoryById($categoryId)
    {

        return $this->db->query("SELECT *, CATEGORY_ID AS CID, 
            (SELECT COUNT(*) FROM shop_categories WHERE PARENT_ID = CID AND shop_categories.DELETED = 0 AND STATUS = 2) AS CHILD_COUNT
        FROM shop_categories
        INNER JOIN shop_categories_lang USING (CATEGORY_ID)
        WHERE STATUS = 2 AND DELETED = 0 AND LANG = ? AND CATEGORY_ID = ?", $this->lang, $categoryId)->fetchArray();

    }

    protected function getCategoryTranslations($categoryId)
    {
        return $this->db->query("SELECT *
        FROM shop_categories_lang
        WHERE CATEGORY_ID = ?", $categoryId)->fetchAll();
    }


    public function getCategoryPage($categoryId)
    {

        $category = $this->getCategoryById($categoryId);
        
        if(!$category){
            $error = new \_class\Error;
            $error->show404();
            return false;
        }

        $slugs = [];

        // echo '<pre>' . print_r(\_class\Registry::load('fullLanguages'), 2) . '</pre>';
        foreach(\_class\Registry::load('fullLanguages') as $lang => $fullLang){
            $slugs[$lang]['slug'] = $this->getUrlByLang($categoryId, $lang);
        }

        $this->document->setOverrideUrlParams($slugs);
        // echo '<pre>' . print_r($slugs, 2) . '</pre>';

        // echo '<pre>' . print_r($translations, 2) . '</pre>';

        $this->categoryId = $category['CATEGORY_ID'];

        $this->document->setMetaTitle("AxleForce | " . $category['TITLE']);

        $this->tpl->define([
            'index' => '/category/tpl/index.html',
            'product_list' => '/category/tpl/product_list.html',
        ]);
        $this->tpl->split_template('index', 'INDEX');
        $this->tpl->split_template('product_list', 'PRODUCT_LIST');

        if($category['CHILD_COUNT'] > 0){

            $imageClass = new \products\CategoryImages;

            $childCategories = $this->getCategoriesByParent($category['CATEGORY_ID']);
            foreach($childCategories as $item){
                $this->tpl->assign_array($item);
                $this->tpl->assign("URL_SLUG", $this->getUrl($item['CATEGORY_ID']));

                $image = $imageClass->getMainImage($item['CATEGORY_ID'], 2);
                $this->tpl->assign("CATEGORY_IMAGE", $image);

                $this->tpl->parse("PAGE_CONTENT", ".child_category_block");
            }

            $productView = new \products\view\ProductView;
            $latest = $productView->getLatestProducts();
            $this->tpl->assign("LATEST_VIEWED", $latest);

            // $featuredSlider = new \products\view\FeaturedSlider();
            // $featuredSlider->getView("FEATURED_SLIDER");

            // $hot = new \products\view\HotSlider;
            // $hot->getView("CAREGORY_PRODUCT_CARDS");

            $this->tpl->assign_array($category);            

            $this->parseSideCategoryMenu();

            $this->tpl->parse("CATEGORY_PAGE_CONTENT", "category_page_content");
            $this->tpl->parse("CONTENT", "index");

            $this->tpl->assign("ADMIN_MENU_CATEGORY_ID", $category['CATEGORY_ID']);
            
        } else {

            $this->tpl->assign_array($category);

            $this->categoryId = $categoryId;

            $filter = new \category\filters\PriceFilter($categoryId);
            $filter->getFilterView();

            $stockFilter = new \category\filters\StockFilter($categoryId);
            $stockFilter->getFilterView();

            if($this->request->get['page']){
                $this->setPage($this->request->get['page']);
            }
            
            if($this->request->get['limit']){
                $this->setLimit($this->request->get['limit']);
            }
            
            if($this->request->get['priceSort']){
                $this->setSortPrice($this->request->get['priceSort']);
            }

            if($this->request->get['filter']){
                $this->setFilter($this->request->get['filter']);
            }

            $this->tpl->assign("PRODUCT_VIEW_OPTIONS", $this->getPageOptions());

            $this->tpl->assign("CURRENT_URL_SLUG", $this->getUrl($categoryId));

            $this->parseSideCategoryMenu();

            // $this->tpl->assign("PAGINATION", $this->getPagination());
            $this->tpl->parse("CONTENT", "product_list");

            $this->tpl->assign("ADMIN_MENU_CATEGORY_ID", $category['CATEGORY_ID']);
        }
    }

    protected function parseSideCategoryMenu()
    {
        if(!$this->categoryId){
            return false;
        }

        $this->tpl->define([
            'index' => '/category/tpl/index.html',
        ]);
        $this->tpl->split_template('index', 'INDEX');

        $category = $this->getCategoryById($this->categoryId);

        $childs1 = $this->getCategoriesByParent(0);
        // $childs1 = $this->getCategoriesByParent($this->categoryId);

        foreach($childs1 as $level1){
            $this->tpl->assign_array([
                "LEVEL_1_CAT_TITLE" => $level1['TITLE'],
                "LEVEL_1_CAT_URL" => $this->getUrl($level1['CATEGORY_ID']),
                "LEVEL_1_IS_CURRENT_CATEGORY" => $this->categoryId == $level1['CATEGORY_ID']
            ]);   

            $childs2 = $this->getCategoriesByParent($level1['CATEGORY_ID']);
            if($childs2){

                $this->tpl->clear_parse("CATEGORY_BLOCK_HAS_CHILD_ROW_L3");

                foreach($childs2 as $level2){
                    $this->tpl->clear_parse("CATEGORY_BLOCK_HAS_CHILD_ROW_L3");
                    $this->tpl->assign_array([
                        "LEVEL_2_CAT_TITLE" => $level2['TITLE'],
                        "LEVEL_2_CAT_URL" => $this->getUrl($level2['CATEGORY_ID']),
                        "LEVEL_2_IS_CURRENT_CATEGORY" => $this->categoryId == $level2['CATEGORY_ID'],
                    ]); 
                    
                    $childs3 = $this->getCategoriesByParent($level2['CATEGORY_ID']);
                    if($childs3){
                        foreach($childs3 as $level3){
                            $this->tpl->assign_array([
                                "LEVEL_3_CAT_TITLE" => $level3['TITLE'],
                                "LEVEL_3_CAT_URL" => $this->getUrl($level3['CATEGORY_ID']),
                                "LEVEL_3_IS_CURRENT_CATEGORY" => $this->categoryId == $level3['CATEGORY_ID']
                            ]);
                            $this->tpl->parse("CATEGORY_BLOCK_HAS_CHILD_ROW_L3", ".category_block_has_child_row_l3");
                        }
                        $this->tpl->clear_parse("CATEGORY_BLOCK_HAS_CHILD_ROW_L3");
                    } else {
                        $this->tpl->assign("CATEGORY_BLOCK_HAS_CHILD_ROW_L3", "");
                        $this->tpl->clear_parse("CATEGORY_BLOCK_HAS_CHILD_ROW_L3");
                    }
                    $this->tpl->parse("CATEGORY_BLOCK_HAS_CHILD_ROW", ".category_block_has_child_row");                    
                }
                
                $this->tpl->parse("CATEGORY_BLOCK", ".category_block_has_child");
                $this->tpl->clear_parse("CATEGORY_BLOCK_HAS_CHILD_ROW_L3");
            } else {
                $this->tpl->parse("CATEGORY_BLOCK", ".category_block_no_child");
                $this->tpl->clear_parse("CATEGORY_BLOCK_HAS_CHILD_ROW_L3");
            }

            $this->tpl->clear_parse("CATEGORY_BLOCK_HAS_CHILD_ROW");            
            $this->tpl->clear_parse("CATEGORY_BLOCK_HAS_CHILD_ROW_L3");
        }



        $this->tpl->parse("SIDE_CATEGORY_MENU", "side_category_menu");
    }

    public function getCategoryProducts()
    {

        if(!$this->categoryId){
            return false;
        }

        $this->tpl->define([
            'product_list' => '/category/tpl/product_list.html',
        ]);
        $this->tpl->split_template('product_list', 'PRODUCT_LIST');

        $result = $this->productQuery();

        if(count($result) <= 0){
            $this->tpl->assign("CURRENT_URL_SLUG", $this->getUrl($this->categoryId));

            $this->tpl->parse("RESULT_HTML", "products_not_found");
            return $this->tpl->fetch("RESULT_HTML");
        }

        $productView = new \products\view\ProductView;

        foreach($result as $itemId){
            $productView->getCategoryListProductView($itemId, "PAGE_PRODUCTS");
        }

        $this->tpl->parse("RESULT_HTML", "product_list_block");
        return $this->tpl->fetch("RESULT_HTML");

    }

    public function getPageOptions()
    {

        $this->tpl->define([
            'product_list' => '/category/tpl/product_list.html',
        ]);
        $this->tpl->split_template('product_list', 'PRODUCT_LIST');
        
        
        // $result = $this->productQuery(true, false, true, false);
        // $this->totalCount = count($result);

        foreach($this->limitOptions as $limit){
            $this->tpl->assign_array([
                "VALUE" => $limit,
                "KEY" => $limit,
                "SELECTED" => $this->limit == $limit ? 'selected' : '',
            ]);
            
            $this->tpl->parse("LIMIT_OPTIONS", ".option");
        }
        
        foreach($this->sortPriceOptions as $value => $key){
            $this->tpl->assign_array([
                "VALUE" => $value,
                "KEY" => $key,
                "SELECTED" => $this->sortPrice == $value ? 'selected' : '',
            ]);
            
            $this->tpl->parse("SORT_OPTIONS", ".option");
        }

        $this->tpl->assign_array([
            // "TOTAL_COUNT" => $this->totalCount,
            "LIMIT" => $this->limit,
            "CURRENT_PAGE" => $this->page,
        ]);

        $this->tpl->parse("RESULT_HTML_OPTIONS", "product_view_options");
        return $this->tpl->fetch("RESULT_HTML_OPTIONS");

    }

    public function getPagination()
    {

        $result = $this->productQuery(true, false, true, false, true);
        $this->totalCount = count($result);
        // var_dump($this->totalCount);
        $pagination = $this->tpl->pagination($this->page, $this->limit, $this->totalCount, 5, '', 'switchPage');
        return $pagination;

    }

    protected function productQuery($useFilters = true, $useLimit = true, $useWhere = true, $useOrder = true)
    {

        $lockedProducts = [];
        $result = $this->db->query("SELECT ITEM_ID, SUM(QTY) AS QTY FROM tbl_order_product_lock GROUP BY ITEM_ID")->fetchAll();
        foreach($result as $row){
            $lockedProducts[$row['ITEM_ID']] = $row['QTY'];
        }

        $where = [];
        $order = [];
        $limit = [];

        $where[] = "STATUS = 2 AND DELETED = 0";

        if($useWhere){
            $where[] = "CATEGORY_ID = " . (int) $this->categoryId;
            $where[] = "LANG = '" . $this->lang . "'";
        }

        if($useFilters){
            if($this->filter['price']){
                if($this->filter['price']['min'] && $this->filter['price']['max']){
                    $where[] = "((STANDART_PRICE >= " . (float) $this->filter['price']['min'] . " AND STANDART_PRICE <= " . (float) $this->filter['price']['max'] . ") OR 
                    (DISCOUNT_PRICE >= " . (float) $this->filter['price']['min'] . " AND DISCOUNT_PRICE <= " . (float) $this->filter['price']['max'] . "))";
                } else {
                    if($this->filter['price']['min']){
                        $where[] = "(STANDART_PRICE >= " . (float) $this->filter['price']['min'] . " OR DISCOUNT_PRICE >= " . (float) $this->filter['price']['min'] . ")";
                    }
                    if($this->filter['price']['max']){
                        $where[] = "(STANDART_PRICE <= " . (float) $this->filter['price']['max'] . " OR DISCOUNT_PRICE <= " . (float) $this->filter['price']['max'] . ")";
                    }
                }
            }
            if($this->filter['stock']){
                $where[] = "(STOCK > 0)";                
            }
        }

        if($useOrder){
            if($this->sortPrice != ''){
                if(in_array($this->sortPrice, ['ASC', 'DESC'])){
                    $order[] = "ORDER BY WEIGHT_ORDER DESC";
                    $order[] = ", SALE_DATE DESC";
                    $order[] = ", NEW_DATE DESC";
                    $order[] = ", FEATURED_DATE DESC";
                    $order[] = ", STANDART_PRICE " . $this->sortPrice;
                    $order[] = ", CREATE_DATE DESC";
                    $order[] = ", ITEM_ID DESC";
                }
            }
        } 

        if(empty($order)){
            $order[] = "ORDER BY WEIGHT_ORDER DESC";
            $order[] = ", SALE_DATE DESC";
            $order[] = ", NEW_DATE DESC";
            $order[] = ", FEATURED_DATE DESC";            
            $order[] = ", CREATE_DATE DESC";
            $order[] = ", ITEM_ID DESC";
        }

        if($useLimit){
            $limit[] = "LIMIT " . (int) ($this->page <= 1 ? 0 : ($this->limit * ($this->page - 1))) . ", " . ((int) $this->limit);
        }

        $whereStr = implode(" AND ", $where);
        $orderStr = implode("", $order);
        $limitStr = implode("", $limit);

        $result = $this->db->query("SELECT ITEM_ID, STOCK
        FROM shop_products
        INNER JOIN shop_products_lang USING (ITEM_ID)
        LEFT JOIN shop_products_flags USING (ITEM_ID)
        INNER JOIN shop_products_category USING (ITEM_ID)
        INNER JOIN shop_products_prices USING (ITEM_ID)
        WHERE {$whereStr}
        {$orderStr}
        {$limitStr}")->fetchAll();

        // var_dump($result); die;

        foreach($result as $i => $row){
            if(in_array($row['ITEM_ID'], array_keys($lockedProducts))){
                $result[$i]['STOCK'] -= (int) $lockedProducts[$row['ITEM_ID']];
            }

            if($this->filter['stock'] && $result[$i]['STOCK'] <= 0){
                unset($result[$i]);
            }
        }

        $resultNew = [];
        foreach($result as $row){
            $resultNew[] = $row['ITEM_ID'];
        }


        // var_dump("SELECT ITEM_ID
        // FROM shop_products
        // INNER JOIN shop_products_lang USING (ITEM_ID)
        // LEFT JOIN shop_products_flags USING (ITEM_ID)
        // INNER JOIN shop_products_category USING (ITEM_ID)
        // INNER JOIN shop_products_prices USING (ITEM_ID)
        // WHERE {$whereStr}
        // {$orderStr}
        // {$limitStr}"); die;

        return $resultNew;
    }

    public function getCategoriesTree($parentId)
    {
        $result = $this->db->query("SELECT * 
        FROM shop_categories
        INNER JOIN shop_categories_lang USING (CATEGORY_ID) WHERE PARENT_ID = 0 AND STATUS = 2 AND DELETED = 0")->fetchAll();

        foreach($result as $row){
            $this->categoryTree[$row['CATEGORY_ID']] = $row;
            $childs = $this->getCategoriesTree($row['PARENT_ID']);
            if($childs){
                $this->categoryTree[$row['CATEGORY_ID']]['childs'] = $childs;
            } else {

            }
            
        } 

        return $this->categoryTree;
    }

    public function buildCategoryTree($parent = 0, $spacing = '', $user_tree_array = '', $level = 0) {
 
        if (!is_array($user_tree_array)){
            $user_tree_array = array();
        }
       
        $result = $this->db->query("SELECT * 
        FROM shop_categories
        INNER JOIN shop_categories_lang USING (CATEGORY_ID) WHERE PARENT_ID = ? AND STATUS = 2 AND DELETED = 0 AND LANG = ?", $parent, $this->lang)->fetchAll();

        foreach($result as $row){
            $user_tree_array[] = array("id" => $row['CATEGORY_ID'], "name" => $spacing . $row['TITLE'], 'level' => $level);
            $user_tree_array = $this->buildCategoryTree($row['CATEGORY_ID'], $spacing . '----&nbsp;&nbsp;', $user_tree_array, $level+1);
            
        }
    
        return $user_tree_array;
    }

    public function buildCategoryTreeAdmin($parent = 0, $spacing = '', $user_tree_array = '', $level = 0) {
 
        if (!is_array($user_tree_array)){
            $user_tree_array = array();
        }
       
        $result = $this->db->query("SELECT * 
        FROM shop_categories
        INNER JOIN shop_categories_lang USING (CATEGORY_ID) WHERE PARENT_ID = ? AND DELETED = 0 AND LANG = ?", $parent, $this->lang)->fetchAll();

        foreach($result as $row){
            $user_tree_array[] = array("id" => $row['CATEGORY_ID'], "name" => $spacing . $row['TITLE'], 'level' => $level);
            $user_tree_array = $this->buildCategoryTreeAdmin($row['CATEGORY_ID'], $spacing . '----&nbsp;&nbsp;', $user_tree_array, $level+1);
            
        }
    
        return $user_tree_array;
    }
      
}