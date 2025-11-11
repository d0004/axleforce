<?php

namespace products\view;

abstract class AbstractClass
{

    protected $tpl;
    protected $db;
    protected $user;
    protected $lang;

    function __construct()
    {
        $this->tpl = \_class\Registry::load('tpl');
        $this->db = \_class\Registry::load('db');
        $this->user = \_class\Registry::load('user');
        $this->lang = \_class\Registry::load('lang');
    }

}