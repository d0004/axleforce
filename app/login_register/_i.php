<?php

include_once('../_main.php');

switch ($a) {
    case 'i_register':

        if(!isset($request->post['lname']) || !isset($request->post['email']) || !isset($request->post['password']) || !isset($request->post['fname'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        if(!$request->post['lname'] || !$request->post['email'] || !$request->post['password'] || !$request->post['fname'] || !$request->post['g-recaptcha-response']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $recaptcha = $request->post['g-recaptcha-response'];
        $secret = RECAPTCHA_SECRET;

        $res = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$recaptcha");
        $result = json_decode($res, true);

        if ($result['success'] && $result['score'] > 0.5) {
            $email = $db->query("SELECT * FROM tbl_user WHERE EMAIL = ?", $request->post['email'])->fetchArray();
            if($email){
                $response->setAjaxOutput(['success' => false, 'error' => 2]);
                break;
            }

            $uid = $user->register($request);
            $user->login($uid);
            $user->prepare();

            $telegram = new \_class\TelegramBot;
            $telegram->sendMessage("🥰 Новый пользователь на сайте\n\n{$request->post['fname']} {$request->post['lname']}\n{$uid}\n{$request->post['email']}\n\nSpam score: {$result['score']}");

            $email = new \email\Email;
            $email->sendTo('after_registration', $request->post['email'], ['LINK' => APP_DOMAIN . $tpl->urlFor('login_register/verify_email', ['code' => $user->userData['VALIDATION_CODE']])]);

            $response->setAjaxOutput(['success' => true]);
        } else {
            $response->setAjaxOutput(['success' => false, 'error' => 132]);
            break;
        }

    break;
    
    case 'i_register_legal':

        if(
            !isset($request->post['vatNumber']) || 
            !isset($request->post['companyName']) || 
            !isset($request->post['companyAddress']) || 
            !isset($request->post['lname']) || 
            !isset($request->post['email']) || 
            !isset($request->post['password']) || 
            !isset($request->post['fname'])
        ){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }
        
        if(
            !$request->post['vatNumber'] || 
            !$request->post['companyName'] || 
            !$request->post['companyAddress'] || 
            !$request->post['lname'] || 
            !$request->post['email'] || 
            !$request->post['password'] || 
            !$request->post['fname'] ||
            !$request->post['g-recaptcha-response']
        ){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $recaptcha = $request->post['g-recaptcha-response'];
        $secret = RECAPTCHA_SECRET;

        $res = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$recaptcha");
        $result = json_decode($res, true);

        if ($result['success'] && $result['score'] > 0.5) {
            $email = $db->query("SELECT * FROM tbl_user WHERE EMAIL = ?", $request->post['email'])->fetchArray();
            if($email){
                $response->setAjaxOutput(['success' => false, 'error' => 2]);
                break;
            }

            $uid = $user->registerLegal($request);
            if(!$uid){
                $response->setAjaxOutput(['success' => false, 'error' => 3]);
                break;
            }

            $user->login($uid);
            $user->prepare();
            
            $telegram = new \_class\TelegramBot;
            $telegram->sendMessage("🥰 Новый пользователь на сайте\n\n{$request->post['fname']} {$request->post['lname']}\n{$uid}\n{$request->post['email']}");

            $email = new \email\Email;
            $email->sendTo('after_registration', $request->post['email'], ['LINK' => APP_DOMAIN . $tpl->urlFor('login_register/verify_email', ['code' => $user->userData['VALIDATION_CODE']])]);

            $response->setAjaxOutput(['success' => true]);
        } else {
            $response->setAjaxOutput(['success' => false, 'error' => 132]);
            break;
        }

    break;

    case "i_login":

        if(!isset($request->post['login']) || !isset($request->post['password'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $result = $db->query("SELECT * FROM tbl_user WHERE EMAIL = ?", $request->post['login'])->fetchArray();
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        if(md5(sha1($request->post['password'])) != $result['PASSWORD']){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);
            break;
        }

        $user->login($result['UID']);
        
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_request_password_reset":
        
        if(!$request->post['email']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $email = $db->query("SELECT * FROM tbl_user WHERE EMAIL = ?", $request->post['email'])->fetchArray();
        if(!$email){
            $response->setAjaxOutput(['success' => true]);
            break;    
        }

        $db->query("UPDATE password_recovery_requests SET STATUS = 9 WHERE EMAIL = ? AND STATUS = 0", $request->post['email']);

        $code = $user->generateRandomString(32);
        $db->query("INSERT INTO password_recovery_requests (EMAIL, CODE, CREATE_DATE, EXPIRE_DATE, REQUEST_IP) VALUES (?, ?, now(), now() + INTERVAL 10 MINUTE, ?)",
            $request->post['email'], $code, $request->server['REMOTE_ADDR']
        );

        $email = new \email\Email;
        $email->sendTo('forgot_password', $request->post['email'], ['LINK' => APP_DOMAIN . $tpl->urlFor('login_register/reset_password', ['code' => $code])]);  

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_password_reset":

        if(!$request->post['code'] || !$request->post['password']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $result = $db->query("SELECT * FROM password_recovery_requests WHERE CODE = ? AND STATUS = 0 AND EXPIRE_DATE > now()", $request->post['code'])->fetchArray();
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $db->query("UPDATE password_recovery_requests SET STATUS = 2 WHERE ID = ?", $result['ID']);
        $db->query("UPDATE tbl_user SET PASSWORD = ? WHERE EMAIL = ?", md5(sha1($request->post['password'])), $result['EMAIL']);
        
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_resend_validation_code":

        $email = new \email\Email;
        $email->sendTo('after_registration', $user->userData['EMAIL'], ['LINK' => APP_DOMAIN . $tpl->urlFor('login_register/verify_email', ['code' => $user->userData['VALIDATION_CODE']])]);
        $response->setAjaxOutput(['success' => true]);

    break;
}



$response->output();
exit;