<?php

namespace payment\pdf\order_info;

use NumberToWords\NumberToWords;

class ShopPayment extends \payment\pdf\proforma\ShopPayment
{

    protected $tplFile = '/payment/tpl/pdf/order_info/shop_payment.html';

    public function getDate($billId)
    {
        $bill = $this->money->getBill($billId);
        if(!$bill){
            return false;
        }

        return $bill['PAYMENT_DATE'];
    }

    protected function save($billId)
    {
        $filePath = $this->getFliePath($billId);
        if(!file_exists($filePath)){
            $this->html2pdf->output($filePath, 'F');
        }
    }

    public function getFliePath($billId)
    {
        $date = $this->getDate($billId);
        @mkdir(FILE_PRIVATE_PATH . '/order_info/');
        $path = FILE_PRIVATE_PATH . '/order_info/' . date('Y-m', strtotime($date));
        @mkdir($path);
        $fileName = date('Y-m-d', strtotime($date)) . '_' . $billId . '.pdf';
        $filePath = $path . '/' . $fileName;
        return $filePath;
    }

}