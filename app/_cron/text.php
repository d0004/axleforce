<?php 

chdir(__DIR__);
include_once("../_main_exe.php");

echo '<pre>' . print_r($_ENV, 2) . '</pre>';