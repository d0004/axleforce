<?php

namespace _class;

class Money
{
    
    protected $db;
    protected $user;

    protected $error;

    function __construct()
    {
        $this->db = \_class\Registry::load('db');
        $this->user = \_class\Registry::load('user');
    }

    public function getError()
    {
        return $this->error;
    }

    public function createBill($uid, $payProfile, $paymentType = '')
    {
        $this->db->query("INSERT INTO tbl_bill (UID, PAY_PROFILE, PAYMENT_TYPE, EXPIRE_DATE) VALUES (?, ?, ?, NOW() + INTERVAL 3 DAY)", $uid, $payProfile, $paymentType);
        if($this->db->affectedRows() > 0){
            return $this->db->lastInsertID();
        }
        return false;
    }

    public function insertBillDetail($billId, $amount, $data, $item = '')
    {
        $bill = $this->db->query("SELECT * FROM tbl_bill WHERE BILL_ID = ?", $billId)->fetchArray();
        if(!$bill){
            return false;
        }
        $this->db->query("INSERT INTO tbl_bill_detail (BILL_ID, AMOUNT, DATA, ITEM) VALUES (?, ?, ?, ?)", $billId, $amount, json_encode($data), $item);
        if($this->db->affectedRows() > 0){
            $this->db->query("UPDATE tbl_bill SET AMOUNT = AMOUNT + ? WHERE BILL_ID = ?", $amount, $billId);
            return true;
        }
        return false;
    }

    public function changePaymentMethod($billId, $method) 
    {
        $paymentClass = \payment\payment_method\Factory::getClass($method);
        if(!($paymentClass instanceof \payment\payment_method\AbstractClass)){
            $this->error = 1;
            return false;
        }

        $bill = $this->getBill($billId);

        if($bill['UID'] != $this->user->uid){
            $this->error = 2;
            return false;
        }

        if($bill['STATUS'] != 0){
            $this->error = 3;
            return false;
        }

        if($bill['PAYMENT_TYPE'] == $method){
            $this->error = 4;
            return false;
        }

        $this->db->query("UPDATE tbl_bill SET PAYMENT_TYPE = ? WHERE BILL_ID = ?", $method, $billId);
        return true;
    }

    public function getBill($billId)
    {
        $bill = $this->db->query("SELECT * FROM tbl_bill WHERE BILL_ID = ?", $billId)->fetchArray();
        if(!$bill){
            return false;
        }

        $bill['detail'] = [];
        $billDetail = $this->db->query("SELECT * FROM tbl_bill_detail WHERE BILL_ID = ?", $billId)->fetchAll();
        foreach($billDetail as $detail){
            $detail['DATA'] = (array) @json_decode($detail['DATA']);
            $bill['detail'][$detail['REC_ID']] = $detail;
        }

        return $bill;
    }

    public function payBill($billId)
    {
        $this->db->query("UPDATE tbl_bill SET STATUS = 2, PAYMENT_DATE = NOW() WHERE STATUS = 0 AND BILL_ID = ?", $billId);
        if($this->db->affectedRows() > 0){
            return true;
        }
        return false;
    }

    public function getOrderByBill($billId)
    {
        return $this->db->query("SELECT *
        FROM tbl_bill_to_order 
        INNER JOIN tbl_order USING (ORDER_ID)
        WHERE BILL_ID = ?", $billId)->fetchArray();
    }

    public function getOperation($id)
    {
        return $this->db->query("SELECT * FROM bank_operations WHERE ID = ?", $id)->fetchArray();
    }

    public function processOperation($id, $billId)
    {
        $this->db->query("UPDATE bank_operations SET STATUS = 2, BILL_ID = ?, PROCESS_DATE = now() WHERE ID = ? AND STATUS IN (0, 1)", $billId, $id);
        if($this->db->affectedRows() <= 0){
            return false;
        }

        return true;
    }

    public function resetOperation($id)
    {
        $this->db->query("UPDATE bank_operations SET STATUS = 1, BILL_ID = 0, PROCESS_DATE = now() WHERE ID = ?", $id);
        if($this->db->affectedRows() <= 0){
            return false;
        }
        
        return true;
    }

}