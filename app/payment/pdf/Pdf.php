<?php

namespace payment\pdf;

use Spipu\Html2Pdf\Html2Pdf;

class Pdf
{

    protected $tpl;
    protected $db;
    protected $user;
    protected $lang;
    protected $money;
    protected $html2pdf;

    function __construct()
    {
        $this->tpl = \_class\Registry::load('tpl');
        $this->db = \_class\Registry::load('db');
        $this->user = \_class\Registry::load('user');
        $this->lang = \_class\Registry::load('lang');
        $this->money = \_class\Registry::load('money');
        $this->html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8');
    }

}