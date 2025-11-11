<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define([
    'index' => '/category/tpl/index.html',
]);
$tpl->split_template('index', 'INDEX');

$category = new \category\Category;
$categoryId = $category->getCategoryBySlug($slug);

// var_dump($categoryId); die;

$category->getCategoryPage($categoryId);



include_once('../_body.php');