<?php

namespace email\templates;

class Factory 
{

    public static function getClass($template)
    {
        switch($template){
            case "after_registration":
                return new \email\templates\AfterRegistration;
            case "forgot_password":
                return new \email\templates\ForgotPassword;
            case "success_payment":
                return new \email\templates\SuccessPayment;
            case "order_ready_to_shipment":
                return new \email\templates\OrderReadyToShipment;
            case "omniva_tracking_code":
                return new \email\templates\OmnivaTrackingCode;
        }

        return false;
    }

}