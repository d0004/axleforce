<?php

namespace payment\process_payment;

class Process
{
    protected $db;
    protected $user;
    protected $money;

    protected $error;
    protected $logId = 0;

    function __construct()
    {
        $this->db = \_class\Registry::load('db');
        $this->user = \_class\Registry::load('user');
        $this->money = \_class\Registry::load('money');
    }

    public function getError()
    {
        return $this->error;
    }

    public function setLogId($logId, $method = "KLIX")
    {
        $this->logId = $method . " " . $logId;
    }

    public function processPayment($billId)
    {
        $telegram = new \_class\TelegramBot;

        $telegram->sendMessage("🟡 Оплата | {$this->logId} | Начата обработка");

        if(!$billId){
            $telegram->sendMessage("🔴 Оплата | {$this->logId} | BILL_ID не был найден");
            $this->error = 101;
            return false;
        }

        $bill = $this->money->getBill($billId);
        if(!$bill){
            $this->error = 102;
            $telegram->sendMessage("🔴 Оплата | {$this->logId} | Переданный BILL_ID не существует");
            return false;
        }

        if($bill['STATUS'] == 2){
            $telegram->sendMessage("🟡 Оплата | {$this->logId} | Счет уже оплачен");
            return false;
        }

        if($bill['STATUS'] != 0){
            $telegram->sendMessage("🟡 Оплата | {$this->logId} | Статус счета не 0 и не 2");
            return false;
        }

        $order = $this->db->query("SELECT * 
        FROM tbl_bill_to_order 
        INNER JOIN tbl_order USING (ORDER_ID)
        WHERE BILL_ID = ? AND tbl_order.STATUS = 0", $bill['BILL_ID'])->fetchArray();

        if(!$order){
            $telegram->sendMessage("🔴 Оплата | {$this->logId} | Оплачено, но заказ не был найден");
            return false;
        }

        if($this->money->payBill($bill['BILL_ID'])){
            $this->db->query("UPDATE tbl_order SET STATUS = 2 WHERE STATUS = 0 AND ORDER_ID = ?", $order['ORDER_ID']);   
            if($this->db->affectedRows() <= 0){
                $telegram->sendMessage("🔴 Оплата | {$this->logId} | Не удалось обновить статус заказа");
                return false;
            }
        
            $this->db->lockTables(['tbl_order_product_lock', 'shop_products']);
            
            $locked = $this->db->query("SELECT * FROM tbl_order_product_lock WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchAll();
            foreach($locked as $lockedProduct){
                
                $this->db->query("UPDATE shop_products SET STOCK = STOCK - ? WHERE ITEM_ID = ?", $lockedProduct['QTY'], $lockedProduct['ITEM_ID']);
                if($this->db->affectedRows() <= 0){
                    $telegram->sendMessage("🟡 Остатки товара | {$this->logId} | Не удалось уменьшить количество товара в таблице shop_products | {$order['ORDER_ID']} | {$lockedProduct['ITEM_ID']}");
                }

                $this->db->query("DELETE FROM tbl_order_product_lock WHERE ITEM_ID = ? AND ORDER_ID = ?", $lockedProduct['ITEM_ID'], $order['ORDER_ID']);
                if($this->db->affectedRows() <= 0){
                    $telegram->sendMessage("🟡 Остатки товара | {$this->logId} | Не удалось снять блокировку остатка с товара | {$order['ORDER_ID']} | {$lockedProduct['ITEM_ID']}");
                }

                $product = $this->db->query("SELECT * FROM shop_products WHERE ITEM_ID = ?", $lockedProduct['ITEM_ID'])->fetchArray();
                if($product['STOCK'] <= 0){
                    $telegram->sendMessage("🟡 Остатки товара | {$product['ITEM_ID']} | {$product['NEW_SKU']} | Осталось: {$product['STOCK']}");
                }
            }

            $this->db->unlockTables();

            $user = $this->db->query("SELECT * FROM tbl_user WHERE UID = ?", $bill['UID'])->fetchArray();

            $telegram->sendMessage("🚀🔥 Оплата | LogId: {$this->logId} | Bill: {$bill['BILL_ID']} | Order: {$order['ORDER_ID']} | 
Успешная оплата на сумму {$bill['AMOUNT']}€
Email: {$user['EMAIL']} | Name: {$user['FNAME']} {$user['LNAME']}");

            $result = $this->db->query("SELECT * FROM tbl_order INNER JOIN tbl_order_delivery USING (ORDER_ID) WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchArray();
            if(!$result){
                $telegram->sendMessage("🔴 Оплата | {$this->logId} | Детали заказа #{$order['ORDER_ID']} не найдены");
                return true;
            } 
            
            $date = date("Y-m-d");
            $path = FILE_PRIVATE_PATH . 'tmp/order_info/' . $date;
            @mkdir($path, 0777, true);
            
            $pdfClass = new \payment\pdf\order_info\ShopPayment;
            $pdfClass->saveTmp($path);
            $pdfClass->getDocument($bill['BILL_ID']);
            $path = $pdfClass->getFliePath($bill['BILL_ID']);

            $email = new \email\Email;
            $email->sendTo('success_payment', $order['EMAIL'], [
                'LINK' => APP_DOMAIN . '/profile/order-history/' . $order['ORDER_ID'],
                'ORDER_ID' => $order['ORDER_ID'],
            ], ['Pasūtījuma informācija' => $path]);  
            
            return true;
        } else {
            $telegram->sendMessage("🔴 Оплата | {$this->logId} | Не удалось оплатить счет");
            return false;
        }
    }
}