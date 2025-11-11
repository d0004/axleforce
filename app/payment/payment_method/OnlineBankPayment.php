<?php

namespace payment\payment_method;

class OnlineBankPayment extends AbstractClass
{

    protected $template = "/payment/tpl/online_bank_payment.html";

    public function getPaymentForm($billId)
    {

        $bill = $this->checkBill($billId);
        if(!$bill){
            return false;
        }

        $this->tpl->define(['online_bank_payment' => $this->template]);
        $this->tpl->split_template("online_bank_payment", "ONLINE_BANK_PAYMENT");



        $this->tpl->assign("BILL_ID", $bill['BILL_ID']);

        $this->tpl->parse("FORM_RESULT", "online_bank_payment");
        $html = $this->tpl->fetch("FORM_RESULT");
        return $html;
    }
}