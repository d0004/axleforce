<?php

namespace email\templates;

abstract class AbstractClass 
{

    protected $db;
    protected $tpl;
    protected $user;

    public function __construct()
    {
        $this->db = \_class\Registry::load('db');
        $this->user = \_class\Registry::load('user');
        $this->tpl = \_class\Registry::load('tpl');
    }

    public function getSubject($data)
    {
        $this->tpl->define(['email_body' => '/email/tpl/' . $this->template]);
        $this->tpl->split_template('email_body', 'EMAIL_BODY');

        if($data){
            $this->tpl->assign_array($data);
        }

        $this->tpl->parse("TMP_SUBJECT", "subject");
        return $this->tpl->fetch("TMP_SUBJECT");
    }

    public function getHtml($data, $files)
    {
        $this->tpl->define(['email_body' => '/email/tpl/' . $this->template]);
        $this->tpl->split_template('email_body', 'EMAIL_BODY');

        if($data){
            $this->tpl->assign_array($data);
        }

        $this->tpl->parse("TMP_RESULT", "email_body");
        return $this->tpl->fetch("TMP_RESULT");
    }
    
    public function getAlt($data, $files)
    {
        $this->tpl->define(['email_body' => '/email/tpl/' . $this->template]);
        $this->tpl->split_template('email_body', 'EMAIL_BODY');

        if($data){
            $this->tpl->assign_array($data);
        }

        $this->tpl->parse("TMP_RESULT_ALT", "email_body_alt");
        return $this->tpl->fetch("TMP_RESULT_ALT");
    }

}