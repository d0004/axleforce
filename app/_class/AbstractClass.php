<?php

namespace _class;

abstract class AbstractClass
{

    protected $tpl;
    protected $db;
    protected $user;
    protected $lang;
    protected $request;
    protected $document;

    function __construct()
    {
        $this->tpl = \_class\Registry::load('tpl');
        $this->db = \_class\Registry::load('db');
        $this->user = \_class\Registry::load('user');
        $this->lang = \_class\Registry::load('lang');
        $this->request = \_class\Registry::load('request');
        $this->response = \_class\Registry::load('response');
        $this->document = \_class\Registry::load('document');
    }

}