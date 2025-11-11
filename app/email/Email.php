<?php

namespace email;

class Email
{

    protected $db;
    protected $tpl;
    protected $user;
    protected $logId;

    protected $template;

    protected $mail;

    public function __construct()
    {

        $this->db = \_class\Registry::load('db');
        $this->user = \_class\Registry::load('user');
        $this->tpl = \_class\Registry::load('tpl');

        $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        // $this->mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $this->mail->isSMTP();                                            //Send using SMTP
        $this->mail->Host       = 'mail.axleforce.lv';                     //Set the SMTP server to send through
        $this->mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $this->mail->Username   = 'noreply@axleforce.lv';                     //SMTP username
        $this->mail->Password   = '?QjqD$6F*6-N+Q9vLPbj8*';                               //SMTP password
        $this->mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $this->mail->Port       = 465;
    
        $this->mail->CharSet = "utf-8";

        $this->mail->setFrom('noreply@axleforce.lv', 'AxleForce SIA');
    }

    public function sendTo($template, $email, $data = [], $files = [])
    {

        $contentClass = \email\templates\Factory::getClass($template);
        if(!($contentClass instanceof \email\templates\AbstractClass)){
            $this->error("Template Error");
            return false;
        }

        $this->template = $template;

        $this->tpl->define(['template' => '/email/tpl/template.html']);
        $this->tpl->split_template('template', 'TEMPLATE');

        $this->tpl->assign_array([
            "APPEAL" => false
        ]);

        $this->tpl->assign("UNSUBSCRIBE_LINK", "https://axleforce.lv/email/unsubscribe/" . $email);

        $emailContent = $contentClass->getHtml($data, $files);
        $this->tpl->assign("EMAIL_HTML_CONTENT", $emailContent);
        $this->tpl->parse("HTML_RESULT", "template");
        $html = $this->tpl->fetch("HTML_RESULT");

        $emailAltContent = $contentClass->getAlt($data, $files);
        $this->tpl->assign("EMAIL_ALT_CONTENT", $emailAltContent);
        $this->tpl->parse("ALT_RESULT", "template_alt");
        $alt = $this->tpl->fetch("ALT_RESULT");

        $subject = $contentClass->getSubject($data);

        $this->logId = $this->logEmail($template, $email, $data, $files);
        $this->sendEmail($email, $subject, $html, $alt, $files);
    }

    public function send($template, $data = [], $files = [])
    {
        
        $contentClass = \email\templates\Factory::getClass($template);
        if(!($contentClass instanceof \email\templates\AbstractClass)){
            $this->error("Template Error");
            return false;
        }

        $this->template = $template;

        if(!$this->user->userData){
            $this->error("User data error");
            return false;
        }

        $this->tpl->define(['template' => '/email/tpl/template.html']);
        $this->tpl->split_template('template', 'TEMPLATE');

        $this->tpl->assign_array([
            "APPEAL" => true,
            "FNAME" => $this->user->userData['FNAME'],
            "LNAME" => $this->user->userData['LNAME'],
        ]);

        $this->tpl->assign("UNSUBSCRIBE_LINK", "https://axleforce.lv/email/unsubscribe/" . $this->user->userData['EMAIL']);

        $emailContent = $contentClass->getHtml($data, $files);
        $this->tpl->assign("EMAIL_HTML_CONTENT", $emailContent);
        $this->tpl->parse("HTML_RESULT", "template");
        $html = $this->tpl->fetch("HTML_RESULT");

        $emailAltContent = $contentClass->getAlt($data, $files);
        $this->tpl->assign("EMAIL_ALT_CONTENT", $emailAltContent);
        $this->tpl->parse("ALT_RESULT", "template_alt");
        $alt = $this->tpl->fetch("ALT_RESULT");

        $subject = $contentClass->getSubject($data);

        $this->logId = $this->logEmail($template, $this->user->userData['EMAIL'], $data, $files);
        $this->sendEmail($this->user->userData['EMAIL'], $subject, $html, $alt, $files);
    }

    protected function sendEmail($email, $subject, $html, $alt, $files = [])
    {

        $result = $this->db->query("SELECT * FROM email_unsubscribe WHERE EMAIL = ?", $email)->fetchArray();
        if($result && $this->template != 'forgot_password'){
            if($this->logId > 0){
                $this->db->query("UPDATE email_log SET SUCCESS = 9, UNSUBSCRIBED = 1 WHERE ID = ?", $this->logId);
            }
        } else {
            try {

                $this->mail->addCustomHeader("List-Unsubscribe",'<ask@axleforce.lv>, <https://axleforce.lv/email/unsubscribe-ok/'.$email.'>');
    
                $this->mail->addAddress($email);
    
                $this->mail->AddEmbeddedImage(PUBLIC_DIR . '/files_public/system/logo.jpg', "logo", "logo.jpg");
    
                foreach($files as $name => $path){
                    if(file_exists($path)){
                        $this->mail->addAttachment($path, $name);
                    }
                }
    
                $this->mail->isHTML(true);
                $this->mail->Subject = $subject;
                $this->mail->Body    = $html;
                $this->mail->AltBody = $alt;
            
                $this->mail->send();
    
                if($this->logId > 0){
                    $this->db->query("UPDATE email_log SET SUCCESS = 2 WHERE ID = ?", $this->logId);
                }
                
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                $this->error("SMTP Error: " . $e->getMessage());
            }
        }
    }

    protected function logEmail($template, $email, $data = [], $files = [])
    {
        $this->db->query("INSERT INTO email_log (EMAIL, CREATE_DATE, DATA, FILES, TEMPLATE, IP) VALUES (?, now(), ?, ?, ?, ?)",
            $email,
            json_encode($data),
            json_encode($files),
            $template,
            $_SERVER['REMOTE_ADDR'] ?: ''
        );

        return $this->db->lastInsertID();
    }

    protected function error($error)
    {
        $telegram = new \_class\TelegramBot;
        $telegram->sendMessage("😭 Не удалось отправить Email\n\n {$error}");
    }

}