<?php

namespace payment\payment_method;

class BankTransferPayment extends AbstractClass
{

    protected $template = "/payment/tpl/bank_transfer_payment.html";

    public function getPaymentForm($billId)
    {

        $bill = $this->checkBill($billId);
        if(!$bill){
            return false;
        }

        $this->tpl->define(['bank_transfer_payment' => $this->template]);
        $this->tpl->split_template("bank_transfer_payment", "BANK_TRANSFER_PAYMENT");

        $this->tpl->assign("AMOUNT", $bill['AMOUNT']);
        $this->tpl->assign("BILL_ID", $bill['BILL_ID']);

        $this->tpl->parse("FORM_RESULT", "bank_transfer_payment");
        $html = $this->tpl->fetch("FORM_RESULT");
        return $html;
    }
}