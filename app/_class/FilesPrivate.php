<?php

namespace _class;

class FilesPrivate extends Files
{

    protected $private = 1;

    function __construct($dir)
    {
        $this->dir = $dir;
    }

}
