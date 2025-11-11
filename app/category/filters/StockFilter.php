<?php

namespace category\filters;

class StockFilter extends AbstractFilter
{

    public function checkShow()
    {
        return true;
    }

    public function getFilterView()
    {

        $this->tpl->define(['price_filter_html' => '/category/tpl/filters.html']);
        $this->tpl->split_template('price_filter_html', 'PRICE_FILTER_HTML');

        $this->tpl->assign("FILTER_IN_STOCK", false);
        if($this->request->get['filter']['stock']){
            $this->tpl->assign("FILTER_IN_STOCK", true);
        }

        $this->tpl->parse('PAGE_FILTERS', '.stock_filter');

    }

}