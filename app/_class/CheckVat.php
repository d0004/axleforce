<?php

namespace _class;

class CheckVat
{

    protected $vat;
    protected $valid;
    protected $name;
    protected $address;
    protected $error;
    protected $errorCode;

    public function check($vatno)
    {
        header('Content-type: application/json; charset=utf8');
        $vatno = str_replace(array(' ', '.', '-', ',', '"'), '', $vatno);
       
        $this->vat = $vatno;

        if (strlen($vatno) <= 2) {
            $this->errorCode = 1;
            $this->error = "Incorrect VAT number";
            return false;
        }

        $client = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
        if(!$client) {
            $this->errorCode = 2;
            $this->error = "web service at ec.europa.eu unavailable";
            return false;
        }

        try {
            $response = $client->checkVat([
                'countryCode' => substr($vatno, 0, 2),
                'vatNumber' => substr($vatno, 2)
            ]);
        } catch (\SoapFault $e) {

            $faults = [
                'INVALID_INPUT'       => 'The provided CountryCode is invalid or the VAT number is empty',
                'SERVICE_UNAVAILABLE' => 'The SOAP service is unavailable, try again later',
                'MS_UNAVAILABLE'      => 'The Member State service is unavailable, try again later or with another Member State',
                'TIMEOUT'             => 'The Member State service could not be reached in time, try again later or with another Member State',
                'SERVER_BUSY'         => 'The service cannot process your request. Try again later.'
            ];

            $error = false;
            if(isset($faults[$e->faultstring])){
                $error = $faults[$e->faultstring];
            }

            if ($error){
                $this->errorCode = 3;
                $this->error = $error;
                return false;
            } else {
                $this->errorCode = 4;
                $this->error = 'Error | ' . $e->faultstring;
                return false;
            }
        }

        if (!$response->valid) {
            $this->errorCode = 5;
            $this->error = 'Not a valid VAT number';
            return false;
        }
 
        $this->valid = $response->valid;
        $this->name = $response->name;
        $this->address = $response->address;
        
        return true;
    }

    public function getResult()
    {
        return [
            'error' => $this->error,
            'errorCode' => $this->errorCode,
            'vat' => $this->vat,
            'valid' => $this->valid,
            'name' => $this->name,
            'address' => $this->address,
        ];
    }

}