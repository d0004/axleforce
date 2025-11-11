<?php

namespace category\filters;

class PriceFilter extends AbstractFilter
{

    public function checkShow()
    {
        return true;
    }

    public function getFilterView()
    {

        // echo '<pre>'; print_r($this->request->get['filter']); echo '</pre>'; die;

        $this->tpl->define(['price_filter_html' => '/category/tpl/filters.html']);
        $this->tpl->split_template('price_filter_html', 'PRICE_FILTER_HTML');

        $minPrice = 0;
        $maxPrice = 10000;

        $result = $this->db->query("SELECT MIN(STANDART_PRICE) AS MIN_SP, MAX(STANDART_PRICE) AS MAX_SP, MIN(DISCOUNT_PRICE) AS MIN_DP, MAX(DISCOUNT_PRICE) AS MAX_DP
        FROM shop_products 
        INNER JOIN shop_products_prices USING (ITEM_ID) 
        INNER JOIN shop_products_category USING (ITEM_ID)
        WHERE CATEGORY_ID = ? AND STATUS = 2 AND DELETED = 0", $this->categoryId)->fetchArray();

        $min = [];
        $min[] = $result['MIN_SP'];
        if($result['MIN_DP'] > 0.00){
            $min[] = $result['MIN_DP'];
        }

        $max = [];
        $max[] = $result['MAX_SP'];
        if($result['MAX_DP'] > 0.00){
            $max[] = $result['MAX_DP'];
        }

        $minPrice = min($min);
        $maxPrice = max($max);

        $currentMinPrice = $minPrice;
        $currentMaxPrice = $maxPrice;
        if($this->request->get['filter'] && $this->request->get['filter']['price']){
            if($this->request->get['filter']['price']['min'] >= $minPrice){
                if($this->request->get['filter']['price']['min'] <= $maxPrice){
                    $currentMinPrice = $this->request->get['filter']['price']['min'];
                }
            }
            if($this->request->get['filter']['price']['max'] >= $minPrice){
                if($this->request->get['filter']['price']['max'] <= $maxPrice){
                    $currentMaxPrice = $this->request->get['filter']['price']['max'];
                }
            }
        }
        
        if($minPrice == $maxPrice){
            return false;
        }

        $this->tpl->assign_array([
            "MIN_PRICE" => $minPrice,
            "MAX_PRICE" => $maxPrice,
            "CURRENT_MIN_PRICE" => $currentMinPrice,
            "CURRENT_MAX_PRICE" => $currentMaxPrice,
        ]);

        $this->tpl->parse('PAGE_FILTERS', '.price_filter');

    }

}