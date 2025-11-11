<?php 

namespace delivery\type;

class CircleK extends AbstractType
{

    protected $station = [];

    function __construct()
    {
        parent::__construct();


        // $data = file_get_contents("https://express.pasts.lv/dusApi/index");
        $data = file_get_contents(__DIR__ . '/../circle.json');
        if($data){
            $dataArray = (array) @json_decode($data, true);
            if($dataArray){
                foreach($dataArray as $item){
                    $this->station[$item['id']] = $item;
                }
            }
        }

        // echo '<pre>'; print_r($this->station); echo '</pre>'; die;
    }

    protected function setType()
    {
        $this->type = 'circlek';
    }

    protected function setTpl()
    {
        $this->tplFile = 'circlek.html';
    }

    public function checkShow(){
        if($this->country != 'LV'){
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

        
        if($this->country == 'LV'){
            if($this->cartClass->totalPrice >= 35){
                $this->price = 0.00;
            } else {
                $this->price = 4.59;
            }
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

        $stations = [];
        foreach($this->station as $item){
            $stations[$item['id']] = ($item['city'] ?  $item['city'] . ' | ' : '') . $item['address'];
        }

        $this->tpl->assign("PRICE_OMNIVA", number_format($this->price, 2, '.', ''));
        $this->tpl->assign("TYPE", $this->type);

        $this->tpl->option_list("STATIONS", '', $stations);

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

        $this->tpl->assign_array([
            "STATION_TITLE" => $data['station_title'],
            "STATION_ADDRESS" => $data['station_address'],
            "STATION_CITY" => $data['station_city'],
        ]);

        $this->tpl->parse("RESULT_HTML", "pay_bill_view");
        $html = $this->tpl->fetch("RESULT_HTML");
        return $html;
    }

    public function prepareData($data){
        $result = [];
        $result['station_title'] = $this->station[$data['station']]['title'];
        $result['station_address'] = $this->station[$data['station']]['address'];
        $result['station_city'] = $this->station[$data['station']]['city'];

        // echo '<pre>'; print_r($result); echo '</pre>';
        // die;
        return $result;
    }

    public function getTitle()
    {
        return 'Latvijas Pasts';
    }

    public function getAddress($data)
    {
        $data = (array) @json_decode($data, true);
        if(!$data){
            return false;
        }

        return $data['station_city'] . ', ' . $data['station_address'] . ' (' . $data['station_title'] . ')';
    }
}