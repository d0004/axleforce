<?php

namespace _class;

class Error extends AbstractClass
{

    public function show404()
    {
        $this->tpl->define(["error_404" => "/system/tpl/404.html"]);
        $this->tpl->parse("CONTENT", "error_404");

        // header("HTTP/1.0 404 Not Found");
    }

    public function show403()
    {
        $this->tpl->define(["error_403" => "/system/tpl/403.html"]);
        $this->tpl->parse("CONTENT", "error_403");

        header("HTTP/1.0 403 Forbidden");
    }
}