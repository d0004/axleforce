<?php

namespace category\filters;

abstract class AbstractFilter extends \_class\AbstractClass
{

    protected $categoryId;

    function __construct($categoryId)
    {
        $this->categoryId = $categoryId;
        parent::__construct();
    }

    public abstract function checkShow();
    public abstract function getFilterView();

}