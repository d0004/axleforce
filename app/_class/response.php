<?php

namespace _class;

class response
{

    private $outputData;

    private $headers = [
        'Content-Type: application/json',
        'Access-Control-Allow-Origin: https://axleforce.lv'
    ];

    public function setAjaxOutput($data)
    {
        $this->outputData = json_encode($data);
    }

    public function output()
    {
        foreach($this->headers as $header){
            header($header);    
        }
        
        echo $this->outputData;
    }

    public function redirect($url)
    {
        header("Location: " . $url);
        die;
    }

    public function setOutput($outputData)
    {
        $this->outputData = $outputData;   
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

}