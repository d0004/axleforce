<?php

namespace payment\payment_method;

class Factory
{

    public static function getClass($paymentType)
    {
        switch($paymentType){
            // case "online_bank":
            //     return new \payment\payment_method\OnlineBankPayment;
            // case "klix":
            //     return new \payment\payment_method\KlixPayment;
            case "revolut":
                return new \payment\payment_method\RevolutPayment;
            // case "cash_on_delivery":
                // return new \payment\payment_method\OnlineBankPayment;
            case "bank_transfer":
                return new \payment\payment_method\BankTransferPayment;
        }
    }
    
}