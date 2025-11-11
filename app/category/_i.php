<?php

include_once('../_main.php');
include_once('./_config.php');

switch ($a) {
    
    case "i_get_category_products":

        if(!isset($request->post['categoryId'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $category = new \category\Category;
        $category->setCategory($request->post['categoryId']);
        $category->setLimit($request->post['limit']);
        $category->setSortPrice($request->post['sortPrice']);
        $category->setPage($request->post['page']);
        $category->setFilter($request->post['filter']);

        $html = $category->getCategoryProducts();
        $optionsHtml = $category->getPageOptions();
        $pagination = $category->getPagination();

        $response->setAjaxOutput([
            'success' => true, 
            'html' => $html,
            'optionsHtml' => $optionsHtml,
            'pagination' => $pagination,
        ]);

    break;

}



$response->output();
exit;