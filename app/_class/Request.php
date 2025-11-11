<?php

namespace _class;

class Request
{

    public $post;
    public $get;
    public $files;
    public $server;
    public $session;
    public $raw;

    function __construct($post, $get, $files, $server, $session)
    {
        $this->post = $post;
        $this->get = $get;
        $this->files = $files;
        $this->server = $server;
        $this->session = $session;
        $this->raw = file_get_contents('php://input');

        unset($_POST);
        unset($_GET);
        unset($_FILES);
        // unset($_SESSION);
        // unset($_SERVER);
    }

}