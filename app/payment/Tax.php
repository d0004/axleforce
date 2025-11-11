<?php

namespace payment;

class Tax
{

    public static function getTax($country)
    {
        if($country == 'LV'){
            return VAT_AMOUNT;
        }

        return false;
    }

}