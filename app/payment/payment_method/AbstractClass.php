<?php

namespace payment\payment_method;

abstract class AbstractClass
{
    protected $tpl;
    protected $db;
    protected $user;
    protected $lang;
    protected $money;

    protected $error;

    function __construct()
    {
        $this->tpl = \_class\Registry::load('tpl');
        $this->db = \_class\Registry::load('db');
        $this->user = \_class\Registry::load('user');
        $this->lang = \_class\Registry::load('lang');
        $this->money = \_class\Registry::load('money');
    }

    protected function checkBill($billId)
    {
        $bill = $this->money->getBill($billId);
        if(!$bill){
            return false;
        }

        return $bill;
    }

    public abstract function getPaymentForm($billId);

    public function getError()
    {
        return $this->error;
    }
}