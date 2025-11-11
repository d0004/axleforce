<?php

namespace payment\payment_method;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class RevolutPayment extends AbstractClass
{

    protected $template = "/payment/tpl/revolut_payment.html";

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

        // $this->setWebhook(); die;

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
                'name' => $row['NEW_SKU'],
                'type' => 'physical',
                'external_id' => $row['ITEM_ID'],
                // 'quantity' => $row['QTY'],
                'quantity' => [
                    'value' => (int) $row['QTY'],
                    'unit' => 'pieces'
                ],
                'unit_price_amount' => (int) ($row['AMOUNT'] * 100),
                'total_amount' => (int) (($row['TOTAL_AMOUNT'] + $row['VAT']) * 100),
                "taxes" => [
                    [
                        "name" => "21% VAT",
                        "amount" => (int) ($row['VAT'] * 100),
                    ],
                ],
            ];
        }

        $delivery = 0.00;
        $deliveryTax = 0.00;
        
        foreach($bill['detail'] as $detail){
            if($detail['ITEM'] == 'delivery'){
                $delivery = $detail['AMOUNT'];
                $deliveryTax = $detail['DATA']['tax'] ?? 0;
            }
        }
        
        $products[] = [
            'name' => "Delivery",
            'type' => 'service',
            'external_id' => 'Delivery',
            'quantity' => [
                'value' => 1,
                'unit' => 'pieces'
            ],
            'unit_price_amount' => (int) ($delivery * 100),
            'total_amount' => (int) (($delivery + $deliveryTax) * 100),
            "taxes" => [
                [
                    "name" => "21% VAT",
                    "amount" => (int) ($deliveryTax * 100),
                ],
            ],
        ];
        

        $post = [
            "amount" => $bill['AMOUNT'] * 100,
            "currency" => "EUR",
            "redirect_url" => APP_DOMAIN . $this->tpl->urlFor("payment/revolut_success_redirect", ["billId" => $billId]),
            // "line_items" => $products,
        ];

        // echo '<pre>' . print_r($post, 2) . '</pre>';

        $curl = new \_class\Mycurl(REVOLUT_API_URL . "/api/orders");
        $curl->setPost(json_encode($post));
        $curl->sethttpHeader([
            'Content-Type: application/json',
            'Accept: application/json',
            'Revolut-Api-Version: 2023-09-01',
            'Authorization: Bearer ' . REVOLUT_SECRET_KEY
        ]);
        $curl->createCurl();

        $result = $curl->__toString();
        $data = (array) @json_decode($result);

        // echo '<pre>' . print_r($data, 2) . '</pre>'; die;

        if($data && $data['checkout_url']){
            $revolutId = $data['id'];
            $this->db->query("INSERT INTO revolut_payment (BILL_ID, REVOLUT_ID, UID, PAYMENT_CREATE_DATA, CREATE_DATE) VALUES (?, ?, ?, ?, now())", 
            $billId, $revolutId, $this->user->uid, $result);
            return $data['checkout_url'];
        }

        $this->db->query("INSERT INTO revolut_payment (BILL_ID, REVOLUT_ID, UID, PAYMENT_CREATE_DATA, CREATE_DATE) VALUES (?, ?, ?, ?, now())", 
        $billId, 0, $this->user->uid, $result);
        
        $this->error = 4;
        return false;
    }

    private function setWebhook()
    {

        // $curl = new \_class\Mycurl(REVOLUT_API_URL . "/api/1.0/webhooks");
        // // $curl->setPost(json_encode($post));
        // $curl->sethttpHeader([
        //     'Content-Type: application/json',
        //     'Accept: application/json',
        //     // 'Revolut-Api-Version: 2023-09-01',
        //     'Authorization: Bearer ' . REVOLUT_SECRET_KEY
        // ]);
        // $curl->createCurl();

        // $result = $curl->__toString();
        // $data = (array) @json_decode($result);
        
        // echo '<pre>' . print_r($data, 2) . '</pre>';

        // die;

        // 9252282c-8687-4ca6-875b-0593089672da

        $post = [
            "url" => APP_DOMAIN . $this->tpl->urlFor("payment/revolut_webhook"),
            "events" => [
                "ORDER_COMPLETED", 
                // "ORDER_AUTHORISED", 
                // "ORDER_PAYMENT_AUTHENTICATED"
            ],
        ];

        $curl = new \_class\Mycurl(REVOLUT_API_URL . "/api/1.0/webhooks");
        $curl->setPost(json_encode($post));
        // $curl->setCustomRequest("PUT");
        $curl->sethttpHeader([
            'Content-Type: application/json',
            'Accept: application/json',
            // 'Revolut-Api-Version: 2023-09-01',
            'Authorization: Bearer ' . REVOLUT_SECRET_KEY
        ]);
        $curl->createCurl();

        $result = $curl->__toString();
        $data = (array) @json_decode($result);
        
        echo '<pre>' . print_r($data, 2) . '</pre>';
    }

    public function webhook($request)
    {
        $telegram = new \_class\TelegramBot;

        $this->db->query("INSERT INTO revolut_success_callback (DATA, BILL_ID, CREATE_DATE) VALUES (?, ?, now())", json_encode($request), 0);
        $logId = $this->db->lastInsertID();

        $data = @json_decode($request->raw, true);
        if(!isset($data) || empty($data)){
            $telegram->sendMessage("🔴 Оплата | {$logId} | RAW данные пришли пустые");
            return false;
        }

        if(!isset($data['order_id']) || !isset($data['event']) || $data['event'] != "ORDER_COMPLETED"){
            $telegram->sendMessage("🔴 Оплата | {$logId} | Нет order_id или event | или event != ORDER_COMPLETED");
            return false;
        }

        if(!in_array($request->server['REMOTE_ADDR'], ["35.246.21.235", "34.89.70.170", "35.242.130.242", "35.242.162.241"])){
            $telegram->sendMessage("🔴 Оплата | {$logId} | IP не из разрешенного списка");
            return false;
        }

        $signature = $request->server["HTTP_REVOLUT_SIGNATURE"];
        $timestamp = $request->server["HTTP_REVOLUT_REQUEST_TIMESTAMP"];
        if(!$signature || !$timestamp){
            $telegram->sendMessage("🔴 Оплата | {$logId} | отсутствует HTTP_REVOLUT_SIGNATURE или HTTP_REVOLUT_REQUEST_TIMESTAMP");
            return false;
        }

        $payload_to_sign = 'v1.' . $timestamp . '.' . $request->raw;
        $signature2 = 'v1=' . hash_hmac('sha256', $payload_to_sign, WEBHOOK_SIGNING_SECRET);
        
        if($signature != $signature2){
            $telegram->sendMessage("🔴 Оплата | {$logId} | Не получилось проверить подпись");
            return false;
        }

        $revolutPayment = $this->db->query("SELECT * FROM revolut_payment WHERE REVOLUT_ID = ? ORDER BY CREATE_DATE DESC LIMIT 1", $data['order_id'])->fetchArray();
        if(!$revolutPayment){
            $this->db->query("UPDATE revolut_success_callback SET ERROR_CODE = ? WHERE ID = ?", 3, $logId);
            $telegram->sendMessage("🔴 Оплата | {$logId} | Не найдена запись в revolut_payment");
            return false;
        }

        $processPayment = new \payment\process_payment\Process;
        $processPayment->setLogId($logId, "revolut");
        if(!$processPayment->processPayment($revolutPayment['BILL_ID'])){
            $this->db->query("UPDATE revolut_success_callback SET ERROR_CODE = ? WHERE ID = ?", $processPayment->getError() ?: 2000, $logId);
            return false;
        }

        return true;
    }
}