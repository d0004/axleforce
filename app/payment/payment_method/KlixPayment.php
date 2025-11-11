<?php

namespace payment\payment_method;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class KlixPayment extends AbstractClass
{

    protected $template = "/payment/tpl/klix_payment.html";

    public function getPaymentForm($billId)
    {

        $bill = $this->checkBill($billId);
        if(!$bill){
            return false;
        }

        $this->tpl->define(['klix_bank_payment' => $this->template]);
        $this->tpl->split_template("klix_bank_payment", "KLIX_BANK_PAYMENT");

        $this->tpl->assign("BILL_ID", $bill['BILL_ID']);

        $this->tpl->parse("FORM_RESULT", "klix_bank_payment");
        $html = $this->tpl->fetch("FORM_RESULT");
        return $html;
    }

    public function processPayment($billId)
    {

        $bill = $this->money->getBill($billId);
        if(!$bill){
            $this->error = 1;
            return false;
        }

        if($bill['UID'] != $this->user->uid){
            $this->error = 2;
            return false;
        }

        if($bill['STATUS'] != 0){
            $this->error = 3;
            return false;
        }

        $order = $this->money->getOrderByBill($billId);
        if(!$order){
            $this->error = 4;
            return false;
        }

        $result = $this->db->query("SELECT * 
        FROM tbl_order_detail 
        INNER JOIN shop_products USING (ITEM_ID)
        WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchAll();

        $products = [];
        foreach($result as $row){
            $products[] = [
                'price' => (int) ($row['TOTAL_AMOUNT'] * 100),
                'name' => $row['NEW_SKU'],
            ];
        }

        $delivery = 0.00;
        $tax = 0.00;
        foreach($bill['detail'] as $detail){
            if($detail['ITEM'] == 'delivery'){
                $delivery = $detail['AMOUNT'];
            }
            if($detail['ITEM'] == 'tax'){
                $tax = $detail['AMOUNT'];
            }
        }

        $products[] = [
            'price' => (int) ($tax * 100),
            'name' => 'VAT',
        ];
        $products[] = [
            'price' => (int) ($delivery * 100),
            'name' => 'Delivery',
        ];
        

        $post = [
            "success_callback" => APP_DOMAIN . $this->tpl->urlFor('payment/klix_success_callback', ['billId' => $billId]),
            "success_redirect" => APP_DOMAIN . $this->tpl->urlFor('payment/klix_success_redirect', ['billId' => $billId]),
            "failure_redirect" => APP_DOMAIN . $this->tpl->urlFor('payment/klix_failure_redirect'),
            "cancel_redirect" => APP_DOMAIN . $this->tpl->urlFor('payment/pay_bill', ['billId' => $billId]),
            "purchase" => [
                "language" => "lv",
                "products" => $products
            ],
            "client" => [
                "email" => $order['EMAIL'],
            ],
            "brand_id" => KLIX_TEST_MODE ? TEST_KLIX_BRAND_ID : LIVE_KLIX_BRAND_ID,
            "reference" => $billId,
        ];

        $curl = new \_class\Mycurl("https://portal.klix.app/api/v1/purchases/");
        $curl->setPost(json_encode($post));
        $curl->sethttpHeader([
            'Accept: application/json',
            'Authorization: Bearer ' . (KLIX_TEST_MODE ? TEST_KLIX_SECRET_KEY : LIVE_KLIX_SECRET_KEY),
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            'Content-Type: application/json',
            'Host: portal.klix.app',
            'accept-encoding: gzip, deflate',
            'cache-control: no-cache',
        ]);
        $curl->createCurl();

        $result = $curl->__toString();
        $data = (array) @json_decode($result);

        if($data && $data['checkout_url']){
            $klixId = $data['id'];
            $this->db->query("INSERT INTO klix_payment (BILL_ID, KLIX_ID, UID, PAYMENT_CREATE_DATA, CREATE_DATE) VALUES (?, ?, ?, ?, now())", 
            $billId, $klixId, $this->user->uid, $result);
            return $data['checkout_url'];
        }

        $this->db->query("INSERT INTO klix_payment (BILL_ID, KLIX_ID, UID, PAYMENT_CREATE_DATA, CREATE_DATE) VALUES (?, ?, ?, ?, now())", 
        $billId, 0, $this->user->uid, $result);
        
        $this->error = 4;
        return false;
    }

    // public function successCallback($request)
    public function successCallback($billId, $request)
    {
        $telegram = new \_class\TelegramBot;
        
        $this->db->query("INSERT INTO klix_success_callback (DATA, BILL_ID, CREATE_DATE) VALUES (?, ?, now())", json_encode($request), $billId);
        $logId = $this->db->lastInsertID();
        

        $klixPayment = $this->db->query("SELECT * FROM klix_payment WHERE BILL_ID = ? ORDER BY ID DESC LIMIT 1", $billId)->fetchArray();
        if(!$klixPayment || !$klixPayment['KLIX_ID']){
            $this->db->query("UPDATE klix_success_callback SET ERROR_CODE = ? WHERE ID = ?", 3, $logId);
            $telegram->sendMessage("🔴 Оплата | {$logId} | Не найдена запись в klix_payment или нет KLIX_ID");
            return false;
        }

        $klixId = $klixPayment['KLIX_ID'];

        try{
            $client = new Client();
            $res = $client->request('GET', 'https://portal.klix.app/api/v1/purchases/' . $klixId . '/', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . (KLIX_TEST_MODE ? TEST_KLIX_SECRET_KEY : LIVE_KLIX_SECRET_KEY),
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'keep-alive',
                    'Host' => 'portal.klix.app',
                    'accept-encoding' => 'gzip, deflate',
                    'cache-control' => 'no-cache',
                ]
            ]);
            if($res->getStatusCode() != 200){
                $this->db->query("UPDATE klix_success_callback SET ERROR_CODE = ? WHERE ID = ?", 4, $logId);
                $telegram->sendMessage("🔴 Оплата | {$logId} | Ошибка при получении статуса");
                return false;
            }
        } catch (RequestException $e){
            $this->db->query("UPDATE klix_success_callback SET ERROR_CODE = ? WHERE ID = ?", 5, $logId);
            $telegram->sendMessage("🔴 Оплата | {$logId} | Ошибка при получении статуса");
            return false;
        }

        $data = $res->getBody();
        $data = (array) @json_decode($data, true);
        if(!$data){
            $this->db->query("UPDATE klix_success_callback SET ERROR_CODE = ? WHERE ID = ?", 6, $logId);
            $telegram->sendMessage("🔴 Оплата | {$logId} | Данные о проверке платежа отсутствуют");
            return false;
        }
        
        $this->db->query("UPDATE klix_success_callback SET RESPONSE = ? WHERE ID = ?", json_encode($data), $logId);

        if($data['status'] != 'paid'){
            $this->db->query("UPDATE klix_success_callback SET ERROR_CODE = ? WHERE ID = ?", 7, $logId);
            $telegram->sendMessage("🔴 Оплата | {$logId} | Заказ не оплачен");
            return false;
        }

        $processPayment = new \payment\process_payment\Process;
        $processPayment->setLogId($logId);
        if(!$processPayment->processPayment($billId)){
            $this->db->query("UPDATE klix_success_callback SET ERROR_CODE = ? WHERE ID = ?", $processPayment->getError() ?: 2000, $logId);
            return false;
        }

        return true;
    }
}