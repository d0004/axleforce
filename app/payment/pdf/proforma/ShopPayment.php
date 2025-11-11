<?php

namespace payment\pdf\proforma;

use NumberToWords\NumberToWords;

class ShopPayment extends \payment\pdf\Pdf
{

    protected $tplFile = '/payment/tpl/pdf/proforma/shop_payment.html';
    protected $tmpPath;

    public function getDate($billId)
    {
        $bill = $this->money->getBill($billId);
        if(!$bill){
            return false;
        }

        return $bill['CREATE_DATE'];
    }

    public function saveTmp($tmpPath)
    {
        $this->tmpPath = $tmpPath;
    }

    public function getDocument($billId)
    {
        $this->tpl->define(['shop_payment' => $this->tplFile]);
        $this->tpl->split_template('shop_payment', 'SHOP_PAYMENT');

        $bill = $this->money->getBill($billId);
        $order = $this->money->getOrderByBIll($billId);

        if(!$bill || !$order){
            return false;
        }

        $date = $this->getDate($bill['BILL_ID']); 

        if($this->user->uid > 0){
            if(!$this->user->hasStatus(40)){
                if($bill['UID'] != $this->user->uid){
                    return false;
                }
            }
        }

        $filePath = $this->getFliePath($bill['BILL_ID']);
        if($filePath && file_exists($filePath)){
            if($this->tmpPath){
                $fileName = date('Y-m-d', strtotime($date)) . '_' . $billId . '.pdf';
                $newFilePath = $this->tmpPath . '/' . $fileName;
                copy($filePath, $newFilePath);
                return true;
                
            } else {
                header("Content-type: application/pdf");
                header("Content-Disposition: inline; filename=filename.pdf");
                @readfile($filePath);
                die;
            }
        }

        if($order['COMPANY_VAT']){
            $this->tpl->assign_array([
                "RECEIVER" => $order['COMPANY_NAME'],
                "RECEIVER_CODE" => $order['COMPANY_VAT'],
                "RECEIVER_ADDRESS" => $order['COMPANY_ADDRESS'],
            ]);
        } else {
            $this->tpl->assign_array([
                "RECEIVER" => $order['NAME'] . ' ' . $order['SURNAME'],
                "RECEIVER_CODE" => '',
                "RECEIVER_ADDRESS" => $order['ADDRESS'],
            ]);
        }

        $orderDetail = $this->db->query("SELECT * 
        FROM tbl_order_detail 
        LEFT JOIN shop_products USING (ITEM_ID) 
        WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchAll();
        
        $i = 1;

        $this->tpl->clear_parse("PRODUCT_ROW");

        foreach($orderDetail as $row){
            $this->tpl->assign_array([
                "NUMBER" => $i,
                "PRODUCT_NAME" => $row['NEW_SKU'],
                "NEW_SKU" => $row['NEW_SKU'],
                "QTY" => $row['QTY'],
                "AMOUNT" => $row['AMOUNT'],
                "TOTAL_AMOUNT" => $row['TOTAL_AMOUNT'],
                "VAT" => $row['VAT'],
            ]);
            $this->tpl->parse("PRODUCT_ROW", ".product_row");

            $i++;
        }

        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('lv');

        list($whole, $decimal) = explode('.', $bill['AMOUNT']);
        $words1 = $numberTransformer->toWords((int) $whole); 
        $words2 = $numberTransformer->toWords((int) $decimal); 

        $this->tpl->assign_array([
            "BILL_ID" => $bill['BILL_ID'],
            "DATE" => date("Y-m-d", strtotime($date)),
            "BILL_TOTAL_PRICE_WITH_VAT" => $bill['AMOUNT'],
            "WORDS1" => $words1,
            "WORDS2" => $words2,
            "NOTE" => $order['NOTES'],
            "PAY_UNTILL" => date("Y-m-d H:i", strtotime($bill['EXPIRE_DATE'])),
        ]);

        $totalWithoutVat = 0.00;
        
        foreach($bill['detail'] as $detail){
            switch($detail['ITEM']){
                case "products":
                    $totalWithoutVat += $detail['AMOUNT'];                    
                break;
                case "tax":
                    $this->tpl->assign_array([
                        "VAT_TOTAL_AMOUNT" => $detail['AMOUNT'],
                    ]);
                break;
                case "delivery":
                   
                    $this->tpl->assign_array([
                        "NUMBER" => $i + 1,
                        "PRODUCT_NAME" => 'Piegāde',
                        "NEW_SKU" => '',
                        "QTY" => 1,
                        "AMOUNT" => $detail['AMOUNT'],
                        "TOTAL_AMOUNT" => $detail['AMOUNT'],
                        "VAT" => $detail['DATA']['tax'],
                    ]);

                    $totalWithoutVat += $detail['AMOUNT'];

                    $this->tpl->parse("PRODUCT_ROW", ".product_row");

                break;
            }
        }

        $this->tpl->assign_array([
            "BILL_TOTAL_PRICE_WITHOUT_VAT" => $totalWithoutVat
        ]);

        $delivery = $this->db->query("SELECT * FROM tbl_order_delivery WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchArray();
        if($delivery){
            $deliveryClass = \delivery\type\Factory::getclass($delivery['DELIVERY_TYPE']);
            if($deliveryClass instanceof \delivery\type\AbstractType){
                $address = $deliveryClass->getAddress($delivery['DELIVERY_DATA']);
                $title = $deliveryClass->getTitle();
                $this->tpl->assign("DELIVERY_ADDRESS", $address ?: '');
                $this->tpl->assign("DELIVERY_COMPANY", $title ?: '');
            }
        }



        $this->tpl->parse("RESULT_HTML", "shop_payment");
        $html = $this->tpl->fetch("RESULT_HTML");
        // echo $html; die;
        
        $this->html2pdf->writeHTML($html);

        $this->save($bill['BILL_ID']);

        if($this->tmpPath){
            $fileName = date('Y-m-d', strtotime($date)) . '_' . $billId . '.pdf';
            $filePath = $this->tmpPath . '/' . $fileName;
            $this->html2pdf->output($filePath, 'F');
        } else {
            $this->html2pdf->output();
            die;
        }
    }

    protected function save($billId)
    {    
        return false;
    }

    public function getFliePath($billId)
    {
        return false;
    }

}