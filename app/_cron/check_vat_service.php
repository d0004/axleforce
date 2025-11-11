<?php

chdir(__DIR__);
include_once("../_main_exe.php");

$arr = [
    'LV40003815611',
    'LV40103299982',
    'LV41203063885',
    'LV40003078817',
    'LV40003346034',
    'LV40003520643',
    'LV40003053029',
    'LV40003768247',
];

$vat = $arr[rand(0, count($arr) - 1)];

$checkVat = new \_class\CheckVat;
$checkVat->check($vat);
$result = $checkVat->getResult();

if($result['errorCode']){
    var_dump($result);
    if(!isset($argv[1]) && $argv[1] != 1){
        $telegram = new \_class\TelegramBot;
        $telegram->sendMessage("Ошибка проверки VAT номеров\nЧто-то опять у них отвалилось 🥳\n\" . $vat . "\n\n" . $result['errorCode'] . " | " . $result['error']);
    }
} else {
    var_dump($checkVat->getResult());
}
