<?php

include_once('../_main.php');
include_once('./_config.php');

$telegram = new \_class\TelegramBot;

$tpl->define(['stripe_success' => '/payment/tpl/stripe_success.html']);
$tpl->split_template('stripe_success', 'STRIPE_SUCCESS');

$featuredSlider = new \products\view\FeaturedSlider();
$featuredSlider->getView("FEATURED_SLIDER");

$db->query("INSERT INTO test (DATA, CREATE_DATE) VALUES (?, now())", print_r($request, 2));

$logId = $db->lastInsertID();

if(!$request->get['sessionId']){

    $telegram->sendMessage("❌ Оплата | {$logId} | GET Session ID не передан (не важная ошибка)");
    
    $tpl->assign("ERROR_CODE", 1);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

$sessionId = $request->get['sessionId'];

$sessionData = $db->query("SELECT * FROM stripe_sessions WHERE STRIPE_ID = ?", $sessionId)->fetchArray();
if(!$sessionData){

    $telegram->sendMessage("❌ Оплата | {$logId} | Не найдена запись в stripe_sessions (надо бы посмотреть, что к чему)");

    $tpl->assign("ERROR_CODE", 2);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

$bill = $money->getBill($sessionData['BILL_ID']);
if(!$bill){

    $telegram->sendMessage("❌ Оплата | {$logId} | Bill не найден (жопа, надо разбираться)");

    $tpl->assign("ERROR_CODE", 3);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

if($bill['UID'] != $user->uid){

    $telegram->sendMessage("❌ Оплата | {$logId} | Пытался открыть не свой счет");

    $response->redirect($tpl->urlFor('index'));
    die;
}

$tpl->assign_array($bill);

if($bill['STATUS'] == 2){
    $tpl->parse("PAGE_CONTENT", "success_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

if($bill['STATUS'] != 0){

    $telegram->sendMessage("❌ Оплата | {$logId} | Статус счета не 0 и не 2 (жопа, надо разбираться)");

    $tpl->assign("ERROR_CODE", 5);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

try{
    $stripe = new \Stripe\StripeClient(
        STRIPE_TEST ? STRIPE_SECRET_KEY_TEST : STRIPE_SECRET_KEY_LIVE
    );
    $checkoutSession = $stripe->checkout->sessions->retrieve($sessionId,[]);
} catch (\Exception $e){

    $telegram->sendMessage("❌ Оплата | {$logId} | checkoutSession данные не удалось получить (жопа, надо разбираться)");

    $tpl->assign("ERROR_CODE", 6);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

if(!$checkoutSession){

    $telegram->sendMessage("❌ Оплата | {$logId} | checkoutSession данные пустые (жопа, надо разбираться)");

    $tpl->assign("ERROR_CODE", 7);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

if($checkoutSession->amount_total != ($bill['AMOUNT'] * 100)){

    $telegram->sendMessage("❌ Оплата | {$logId} | Сумма счета не равна сумме оплаты (жопа, надо разбираться)");

    $tpl->assign("ERROR_CODE", 8);    
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

if($checkoutSession->payment_status != 'paid'){

    $telegram->sendMessage("❌ Оплата | {$logId} | Счет в stripe не оплачен (странно, надо глянуть)");

    $tpl->assign("ERROR_CODE", 9);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

$order = $db->query("SELECT * 
FROM tbl_bill_to_order 
INNER JOIN tbl_order USING (ORDER_ID)
WHERE BILL_ID = ? AND tbl_order.STATUS = 0", $bill['BILL_ID'])->fetchArray();

if(!$order){

    $telegram->sendMessage("❌ Оплата | {$logId} | Order не найден или уже оплачен (надо разбираться)");

    $tpl->assign("ERROR_CODE", 10);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

if($money->payBill($bill['BILL_ID'])){
    $db->query("UPDATE tbl_order SET STATUS = 2 WHERE STATUS = 0 AND ORDER_ID = ?", $order['ORDER_ID']);   
    if($db->affectedRows() <= 0){

        $telegram->sendMessage("❌ Оплата | {$logId} | Не удалось обновить статус заказа (надо разбираться)");

        $tpl->assign("ERROR_CODE", 11);
        $tpl->parse("PAGE_CONTENT", "error_in_payment");
        $tpl->parse("CONTENT", "stripe_success");
        include_once('../_body.php');
        die;
    }


    $telegram->sendMessage("✅ Оплата | {$logId} | Успешная оплата на сумму {$bill['AMOUNT']}€");
    $tpl->parse("PAGE_CONTENT", "success_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;

} else {

    $telegram->sendMessage("❌ Оплата | {$logId} | Не удалось обновить статус счета (надо разбираться)");

    $tpl->assign("ERROR_CODE", 12);
    $tpl->parse("PAGE_CONTENT", "error_in_payment");
    $tpl->parse("CONTENT", "stripe_success");
    include_once('../_body.php');
    die;
}

$tpl->parse("CONTENT", "stripe_success");
include_once('../_body.php');