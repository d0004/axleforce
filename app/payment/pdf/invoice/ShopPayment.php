<?php

namespace payment\pdf\invoice;

use NumberToWords\NumberToWords;

class ShopPayment extends \payment\pdf\proforma\ShopPayment
{

    protected $tplFile = '/payment/tpl/pdf/invoice/shop_payment.html';

    public function getDate($billId)
    {

        $order = $this->db->query("SELECT * FROM tbl_bill_to_order WHERE BILL_ID = ?", $billId)->fetchArray();
        if($order){
            $confirmationDate = $this->db->query("SELECT * FROM tbl_order_confirmed WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchArray();
            if($confirmationDate){
                return $confirmationDate['CREATE_DATE'];
            }
        }

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
        @mkdir(FILE_PRIVATE_PATH . 'invoices/');
        $path = FILE_PRIVATE_PATH . 'invoices/' . date('Y-m', strtotime($date));
        @mkdir($path);
        $fileName = date('Y-m-d', strtotime($date)) . '_' . $billId . '.pdf';
        $filePath = $path . '/' . $fileName;
        return $filePath;
    }

}