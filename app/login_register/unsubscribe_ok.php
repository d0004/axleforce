<?php

include_once('../_main.php');
include_once('./_config.php');

$result = $db->query("SELECT * FROM tbl_user WHERE EMAIL = ?", $email)->fetchArray();
if($result){
    $db->query("INSERT IGNORE INTO email_unsubscribe (EMAIL, CREATE_DATE, IP) VALUES (?, now(), ?)", $email, $request->server['REMOTE_ADDR']);
    if($db->affectedRows() > 0){
        $telegram = new \_class\TelegramBot;
        $telegram->sendMessage("😭 {$email} | Отписался от почты");
    }
}

$tpl->define(['email_unsubscribe' => '/login_register/tpl/email_unsubscribe.html']);
$tpl->split_template('email_unsubscribe', 'EMAIL_UNSUBSCRIBE');

$tpl->parse("CONTENT", "email_unsubscribe_ok");
include_once('../_body.php');