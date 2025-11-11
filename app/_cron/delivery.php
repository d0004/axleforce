<?php

chdir(__DIR__);
include_once("../_main_exe.php");

$pacomate = [];

$data = file_get_contents("https://www.omniva.lv/locations.json");
file_put_contents(__DIR__ . '/../delivery/omniva.json', $data);

$data = file_get_contents("https://express.pasts.lv/dusApi/index");
file_put_contents(__DIR__ . '/../delivery/circle.json', $data);
