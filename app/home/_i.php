<?php

include_once('../_main.php');
include_once('./_config.php');

switch ($a) {
    
    case "i_chack_vat_number":

        if(!$request->post['vatNumber']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $checkVat = new \_class\CheckVat;
        $checkVat->check($request->post['vatNumber']);
        $result = $checkVat->getResult();

        if($result['errorCode']){
            $response->setAjaxOutput(['success' => false, 'error' => 2 . '-' . $result['errorCode'], 'errorMessgae' => $result['error']]);
            break;
        }

        $response->setAjaxOutput(['success' => true, 'data' => $result]);
        break;

        // $countryCode = substr($request->post['vatNumber'], 0, 2);
        // $vatNumber = substr($request->post['vatNumber'], 2, strlen($request->post['vatNumber']));

        // if(strlen($vatNumber) < 8){
        //     $response->setAjaxOutput(['success' => false, 'error' => 3]);
        //     break;
        // }

        // $data = [];

        // try{
        //     $client = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
        //     $data = $client->checkVat([
        //         'countryCode' => strtoupper($countryCode),
        //         'vatNumber' => $vatNumber
        //     ]);
        // } catch (\Exception $e){
        //     if($e->getMessage() == "MS_UNAVAILABLE"){
        //         $response->setAjaxOutput(['success' => false, 'error' => 90]);
        //         break;    
        //     }
        //     $response->setAjaxOutput(['success' => false, 'error' => 2]);
        //     break;
        // }
        
        // if(!$data){
        //     $response->setAjaxOutput(['success' => false, 'error' => 3]);
        //     break;
        // }

        // $result = [];
        // $result['valid'] = $data->valid;
        // $result['name'] = $data->name;
        // $result['address'] = $data->address;

        // $response->setAjaxOutput(['success' => true, 'data' => $result]);

    break;

    case "i_subscribe":
    
        if(!$request->post['email']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        if (!filter_var($request->post['email'], FILTER_VALIDATE_EMAIL)) {
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $db->query("INSERT IGNORE INTO tbl_subscribes (EMAIL) VALUES (?)", $request->post['email']);

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_check_email":

        if(!$request->post['email']){
            $response->setAjaxOutput(false);
            break;
        }

        $email = $db->query("SELECT * FROM tbl_user WHERE EMAIL = ?", $request->post['email'])->fetchArray();
        if($email){
            $response->setAjaxOutput(false);
            break;
        }

        $response->setAjaxOutput(true);

    break;

    case "i_contact_us":

        if(!$request->post['name'] || !$request->post['email'] || !$request->post['subject'] || !$request->post['message'] || !$request->post['g-recaptcha-response']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $recaptcha = $request->post['g-recaptcha-response'];
        $secret = RECAPTCHA_SECRET;

        $res = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$recaptcha");
        $result = json_decode($res, true);

        if ($result['success'] && $result['score'] > 0.5) {
            $db->query("INSERT INTO messages (NAME, EMAIL, SUBJECT, MESSAGE, CREATE_DATE) VALUES (?, ?, ?, ?, now())", $request->post['name'], $request->post['email'], $request->post['subject'], $request->post['message']);
            if($db->affectedROws() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 2]);
                break;
            }

            $telegram = new \_class\TelegramBot;
            $telegram->sendMessage("🟡 Сообщение на сайте\n
    Имя:    {$request->post['name']}
    Почта:  {$request->post['email']}
    Тема:   {$request->post['subject']}
    Spam score:   {$result['score']}
    IP:   {$request->server['REMOTE_ADDR']}
    -------------------------------------
    Сообщение: {$request->post['message']}");

            $response->setAjaxOutput(['success' => true]);
        } else {
            $response->setAjaxOutput(['success' => false, 'error' => 132]);
        }

    break;
}



$response->output();
exit;