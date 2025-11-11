<?php

chdir(__DIR__);
include_once('../_main_exe.php');

$delivery = new \delivery\type\LatvijasPasts;
$delivery->create();