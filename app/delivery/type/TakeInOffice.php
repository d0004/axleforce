<?php 

namespace delivery\type;

class TakeInOffice extends AbstractType
{

    function __construct()
    {
        parent::__construct();
    }

    protected function setType()
    {
        $this->type = 'take_in_office';
    }

    protected function setTpl()
    {
        $this->tplFile = 'take_in_office.html';
    }

    public function checkShow()
    {
        if($this->country == "LV"){
            return true;
        }

        return false;
    }

    public function setPrice()
    {
        $this->price = 0.00;
        return true;

    }

    public function getForm($first){

        if(!$this->country){
            return false;
        }

        $notShippable = $this->getNotShippableProducts();

        $this->tpl->define([$this->type => "/delivery/tpl/" . $this->tplFile]);
        $this->tpl->split_template($this->type, strtoupper($this->type));

        $this->tpl->assign("FIRST", $first);

        $this->tpl->assign("PRICE_TAKE_IN_OFFICE", number_format($this->price, 2, '.', ''));
        $this->tpl->assign("TYPE", $this->type);

        if($notShippable){

            foreach($notShippable as $product){

                $imageClass = new \products\ProductImages;
                $image = $imageClass->getMainImage($product['ITEM_ID'], 3);
                $this->tpl->assign("NOT_SHIPPABLE_PRODUCT_IMAGE", $image);
                $this->tpl->assign("NOT_SHIPPABLE_PRODUCT_NEW_SKU", $product['NEW_SKU']);
                $this->tpl->assign("NOT_SHIPPABLE_PRODUCT_QTY", $product['QTY']);

                $this->tpl->parse("NOT_SHIPPABLE_ROW", ".not_shippable_row");
            }

            $this->tpl->parse("NOT_SHIPPABLE", "not_shippable");
        }


        $this->tpl->parse("RESULT_HTML", "full_form");
        $html = $this->tpl->fetch("RESULT_HTML");
        return $html;
    }

    public function getPayBillView($data)
    {
        $this->tpl->define([$this->type => "/delivery/tpl/" . $this->tplFile]);
        $this->tpl->split_template($this->type, strtoupper($this->type));   

        $this->tpl->parse("RESULT_HTML", "pay_bill_view");
        $html = $this->tpl->fetch("RESULT_HTML");
        return $html;
    }

    public function getTitle()
    {
        return 'Take in office';
    }

    public function prepareData($data){
        return [];
    }

    public function getAddress($data)
    {
        return 'Spilves iela 8b, LV-1055';
    }
}