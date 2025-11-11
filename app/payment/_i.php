<?php

include_once('../_main.php');
include_once('./_config.php');

switch ($a) {
    
    // case "i_stripe_make_payment":

    //     if(!$request->post['billId']){
    //         $response->setAjaxOutput(['success' => false, 'error' => 1]);
    //         break;
    //     }
        
    //     $bill = $money->getBill($request->post['billId']);
    //     if(!$bill){
    //         $response->setAjaxOutput(['success' => false, 'error' => 2]);
    //         break;
    //     }

    //     if($bill['UID'] != $user->uid){
    //         $response->setAjaxOutput(['success' => false, 'error' => 3]);
    //         break;
    //     }

    //     if($bill['STATUS'] != 0){
    //         $response->setAjaxOutput(['success' => false, 'error' => 4]);
    //         break;
    //     }
       
    //     try{
    //         $stripe = new \Stripe\StripeClient(
    //             STRIPE_TEST ? STRIPE_SECRET_KEY_TEST : STRIPE_SECRET_KEY_LIVE
    //         );
    //         $checkoutSession = $stripe->checkout->sessions->create([
    //             'success_url' => APP_DOMAIN . $tpl->urlFor('payment/stripe_success') . '?sessionId={CHECKOUT_SESSION_ID}',
    //             'cancel_url' => APP_DOMAIN . $tpl->urlFor('payment/stripe_cancel'),
    //             'payment_method_types' => ['card'],
    //             'line_items' => [
    //                 [
    //                     'name' => "Payment for bill #" . $bill['BILL_ID'],
    //                     'amount' => $bill['AMOUNT'] * 100,
    //                     'quantity' => 1,
    //                     'currency' => 'EUR',
    //                 ],
    //             ],
    //             'mode' => 'payment',
    //         ]);
    //     } catch (\Exception $e){
    //         $response->setAjaxOutput(['success' => false, 'error' => 5]);
    //         break;
    //     }

    //     if(!$checkoutSession->id){
    //         $response->setAjaxOutput(['success' => false, 'error' => 6]);
    //         break;
    //     }
        
    //     $data = [
    //         'id' => $checkoutSession->id,
    //         'amount_total' => $checkoutSession->amount_total,
    //         'currency' => $checkoutSession->currency,
    //         'payment_intent' => $checkoutSession->payment_intent,
    //     ];

    //     $db->query("INSERT IGNORE INTO stripe_sessions (BILL_ID, STRIPE_ID, DATA, CREATE_DATE) VALUES (?, ?, ?, now())", $bill['BILL_ID'], $checkoutSession->id, json_encode($data));
    //     if($db->affectedRows() <= 0){
    //         $response->setAjaxOutput(['success' => false, 'error' => 7]);
    //         break;
    //     }

    //     $response->setAjaxOutput(['success' => true, 'url' => $checkoutSession->url]);

    // break;

    case "i_klix_make_payment":
    
        if(!$request->post['billId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $klix = new \payment\payment_method\KlixPayment;
        $result = $klix->processPayment($request->post['billId']);
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => $klix->getError()]);
            break;
        }

        $response->setAjaxOutput(['success' => true, 'url' => $result]);

    break;
    
    case "i_revolut_make_payment":
    
        if(!$request->post['billId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $revolut = new \payment\payment_method\RevolutPayment;
        $result = $revolut->processPayment($request->post['billId']);
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => $revolut->getError()]);
            break;
        }

        $response->setAjaxOutput(['success' => true, 'url' => $result]);

    break;

}

$response->output();
exit;