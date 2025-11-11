<?php 

namespace delivery\type;

class LatvijasPasts extends AbstractType
{

    protected $directions = [];

    function __construct()
    {
        parent::__construct();

        $data = file_get_contents(__DIR__ . '/../latvijas_pasts.json');
        if($data){
            $dataArray = (array) @json_decode($data, true);
            if($dataArray){
                foreach($dataArray as $country => $item){
                    $this->directions[$country] = $item;
                }
            }
        }

        // echo '<pre>'; print_r($this->directions); echo '</pre>'; die;
    }

    protected function setType()
    {
        $this->type = 'lvpasts';
    }

    protected function setTpl()
    {
        $this->tplFile = 'latvijas_pasts.html';
    }

    public function checkShow()
    {
        if(!isset($this->directions[$this->country])){
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

        if(!isset($this->directions[$this->country])){
            return false;
        }

        $directionParams = $this->directions[$this->country];
        $products = $this->cartClass->cartProducts;

        $totalWeightKg = array_reduce($products, function($carry, $item) {
            return $carry += $item['WEIGHT'];
        }, 0);

        if($totalWeightKg <= 0.250){
            $this->price = $directionParams['250g'];
            return true;
        }

        if($totalWeightKg <= 0.500){
            $this->price = $directionParams['500g'];
            return true;
        }

        if($totalWeightKg <= 1.000){
            $this->price = $directionParams['1kg'];
            return true;
        }

        $extra = $totalWeightKg - 1;
        $this->price = (float) round($directionParams['1kg'] + ($directionParams['extra_per_kg'] * ceil($extra)), 2);
        return true;
    }

    public function getForm($first){

        if(!$this->country){
            return false;
        }

        $this->tpl->define([$this->type => "/delivery/tpl/" . $this->tplFile]);
        $this->tpl->split_template($this->type, strtoupper($this->type));

        $this->tpl->assign("FIRST", $first);

        // $pacomates = [];
        // foreach($this->pacomate[$this->country] as $item){
        //     $pacomates[$item['ZIP']] = $item['NAME'];
        // }

        $this->tpl->assign("PRICE_OMNIVA", number_format($this->price, 2, '.', ''));
        $this->tpl->assign("TYPE", $this->type);

        // $this->tpl->option_list("PACOMATE", '', $pacomates);

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
            "LVP_COUNTRY" => \_class\Registry::load('countries')[$data['country']] ?? "--",
            "LVP_CITY" => $data['city'],
            "LVP_STREET" => $data['street'],
            "LVP_HOUSE" => $data['house'],
            "LVP_APARTMENT" => $data['apartment'],
            "LVP_ZIP" => $data['zip'],
        ]);

        $this->tpl->parse("RESULT_HTML", "pay_bill_view");
        $html = $this->tpl->fetch("RESULT_HTML");
        return $html;
    }

    public function prepareData($data)
    {
        $data['country'] = $this->country;
        return $data;
    }

    public function getTitle()
    {
        return 'Latvijas pasts';
    }

    public function getAddress($data)
    {
        $data = (array) @json_decode($data, true);
        if(!$data){
            return false;
        }

        return join(', ', $data);
    }

    public function create($data)
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://express.pasts.lv',
        ]);

        try{

            $totalWeight = 0;
            $parcelContent = [];
            foreach($data['PRODUCTS_IN_ORDER'] as $item){
                $parcelContent[] =  [
                    "title" => $item['NEW_SKU'],
                    "amount" => $item['QTY'],
                    "weight" => (float) $item['WEIGHT'],
                    "value" => $item['AMOUNT'],
                    "hs_code" => 440500,
                    "origin_country_id" => 7,
                ];

                $totalWeight += (float) $item['WEIGHT'];
            }

            $parcels = [
                [
                    "type" => "Ems",
                    "package_weight" => $totalWeight,
                    "country_id" => $this->countries($data['DELIVERY_DATA']['country']),
                    "city" => $data['DELIVERY_DATA']['city'],
                    "street" => $data['DELIVERY_DATA']['street'],
                    "house" => $data['DELIVERY_DATA']['house'],
                    "apartment_nr" => $data['DELIVERY_DATA']['apartment'],
                    "zipcode" => $data['DELIVERY_DATA']['zip'],
                    "phone" => $data['PHONE'],
                    "email" => $data['EMAIL'],
                    "package_contents" => "Prece",
                    "content_currency" => "EUR",
                    "ParcelContent" => $parcelContent,

                    "name_surname" => $data['NAME'] . ' ' . $data['SURNAME'],
                    "irreversible_action" => "TF",

                    "Sender" => [
                        "company_name" => "SIA AxleForce",
                        "phone" => "+37125685778",
                        "city" => "Riga",
                        "street" => "Spilves iela",
                        "house" => "8b",
                        "zipcode" => "LV-1055",
                    ],
                ]
            ];

            // echo '<pre>' . print_r($parcels, 2) . '</pre>'; die;

            $response = $client->request('POST', '/api/apiPackage/create', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'auth' => "0A5624954FED45cEbB3a8937036FF96b",
                    'parcels' => $parcels,
                ]
            ]);

            $body = $response->getBody();
            $contents = $body->getContents();

            $contents = json_decode($contents, true);

            return $contents[0];
        } catch (\Exception $e){
            // echo '<pre>' . print_r($e->getMessage(), 2) . '</pre>';
            return false;
        }

        return false;
    }

    public function label($data)
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://express.pasts.lv',
        ]);

        try{
            $response = $client->request('POST', '/api/apiPrint/labels', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'auth' => "0A5624954FED45cEbB3a8937036FF96b",
                    "fit_all" => 0,
                    "parcels" => [
                        $data
                    ],
                ]
            ]);

            $body = $response->getBody();
            $contents = $body->getContents();

            return $contents;
        } catch (\Exception $e){
            echo '<pre>' . print_r($e->getMessage(), 2) . '</pre>';
            return false;
        }

        return false;
    }


    protected function countries($iso)
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://express.pasts.lv',
        ]);

        $response = $client->request('GET', '/countriesApi/index');
        $body = $response->getBody();
        $contents = $body->getContents();
        $contents = json_decode($contents, true);

        $iso2Column = array_column($contents, 'iso2');
        $key = array_search($iso, $iso2Column);

        if(!isset($contents[$key]['id'])){
            throw new \Exception("Country not found");
        }

        return $contents[$key]['id'];
    }
}