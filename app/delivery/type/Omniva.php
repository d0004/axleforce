<?php 

namespace delivery\type;

class Omniva extends AbstractType
{

    protected $pacomate = [];

    function __construct()
    {
        parent::__construct();


        // $data = file_get_contents("https://www.omniva.lv/locations.json");
        $data = file_get_contents(__DIR__ . '/../omniva.json');
        if($data){
            $dataArray = (array) @json_decode($data, true);
            if($dataArray){
                foreach($dataArray as $item){
                    $this->pacomate[$item['A0_NAME']][$item['ZIP']] = $item;
                }
            }
        }

        // echo '<pre>'; print_r($this->pacomate['LV']); echo '</pre>'; die;
    }

    protected function setType()
    {
        $this->type = 'omniva';
    }

    protected function setTpl()
    {
        $this->tplFile = 'omniva.html';
    }

    public function checkShow()
    {
        if(!isset($this->pacomate[$this->country])){
            return false;
        }

        if(!$this->checkShippableCategory()) return false;
        if(!$this->checkShippableProducts()) return false;

        return true;
    }

    public function setPrice()
    {
        if(!$this->country){
            return false;
        }

        if($this->cartClass->totalPrice >= 35){
            $this->price = 0.00;
            return true;
        }

        if($this->country == 'LV'){
            $this->price = 3.29;
        } elseif($this->country == 'LT'){
            $this->price = 6.32;
        } elseif($this->country == 'EE'){
            $this->price = 6.32;
        } else {
            return false;
        }

        return true;

    }

    public function getForm($first){

        if(!$this->country){
            return false;
        }

        $this->tpl->define([$this->type => "/delivery/tpl/" . $this->tplFile]);
        $this->tpl->split_template($this->type, strtoupper($this->type));

        $this->tpl->assign("FIRST", $first);

        $pacomates = [];
        foreach($this->pacomate[$this->country] as $item){
            $pacomates[$item['ZIP']] = $item['NAME'];
        }

        $this->tpl->assign("PRICE_OMNIVA", number_format($this->price, 2, '.', ''));
        $this->tpl->assign("TYPE", $this->type);

        $this->tpl->option_list("PACOMATE", '', $pacomates);

        $this->tpl->parse("RESULT_HTML", "full_form");
        $html = $this->tpl->fetch("RESULT_HTML");
        return $html;
    }

    public function getPayBillView($data)
    {
        $this->tpl->define([$this->type => "/delivery/tpl/" . $this->tplFile]);
        $this->tpl->split_template($this->type, strtoupper($this->type));   

        $data = (array) @json_decode($data, true);
        if(!$data){
            return false;
        }

        $this->tpl->assign("PACOMATE_DATA", $data['pacomate_name']);

        $this->tpl->parse("RESULT_HTML", "pay_bill_view");
        $html = $this->tpl->fetch("RESULT_HTML");
        return $html;
    }

    public function prepareData($data){
        $result = [];
        $result['pacomate_name'] = $this->pacomate[$this->country][$data['pacomate']]['NAME'];
        return $result;
    }

    public function getTitle()
    {
        return 'Omniva';
    }

    public function getAddress($data)
    {
        $data = (array) @json_decode($data, true);
        if(!$data){
            return false;
        }

        return $data['pacomate_name'];
    }
}