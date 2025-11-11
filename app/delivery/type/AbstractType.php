<?php 

namespace delivery\type;

abstract class AbstractType extends \_class\AbstractClass
{

    protected $type;
    protected $tplFile;

    protected $country;

    protected $price = 0.00;
    protected $cartClass;

    function __construct()
    {
        parent::__construct();
        $this->setType();
        $this->setTpl();

        $this->cartClass = new \products\ProductCart();

    }

    abstract protected function setType();
    abstract protected function setTpl();
    abstract public function setPrice();
    abstract public function checkShow();
    abstract public function getForm($first);
    abstract public function getPayBillView($data);
    abstract public function prepareData($data);
    abstract public function getAddress($data);
    abstract public function getTitle();

    public function setCountry($country){

        if(array_key_exists($country, \_class\Registry::load('countries'))){
            $this->country = $country;
            return true;
        }

        return false;
    }

    public function getPrice()
    {
        return $this->price;
    }
    
    public function getType()
    {
        return $this->type;
    }

    protected function checkShippableCategory()
    {
        $categories = $this->cartClass->getCartCategories();
        foreach($categories as $category){
            if($category['SHIPPABLE'] == 0){
                return false;
            }
        }

        return true;
    }

    protected function checkShippableProducts()
    {
        $products = $this->cartClass->cartProducts;
        foreach($products as $product){
            if($product['SHIPPABLE'] == 0){
                return false;
            }
        }

        return true;
    }

    protected function getNotShippableProducts()
    {
        $productIds = [];
        
        $products = $this->cartClass->cartProducts;
        foreach($products as $product){

            if($product['SHIPPABLE'] == 0){
                $productIds[$product['ITEM_ID']] = $product;
            }

            foreach($product['CATEGORY'] as $category){
                if($category['SHIPPABLE'] == 0){
                    $productIds[$product['ITEM_ID']] = $product;
                }
            }
        }

        return $productIds;
    }
    
}