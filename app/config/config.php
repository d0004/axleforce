<?php

define("VAT_AMOUNT", 0.21);
define("VAT_AMOUNT_2", 1.21);

define("FILE_PUBLIC_PATH", PUBLIC_DIR . "/files_public/");
define("FILE_PRIVATE_PATH", ROOT_DIR . "/files_private/");

define("SERVER_PATH", PUBLIC_DIR);

define("BLANK_IMAGE", "/assets/images/blank.jpg");

define('APP_DOMAIN', 'https://axleforce.lv');


define("TELEGRAM_BOT_TOKEN", "1713279865:AAHLzX4RJlkdXokrGBjW7qO-Kjqyd7ks5gU");
define("TELEGRAM_CHAT_ID", "-1001494338801");

define("RECAPTCHA_SECRET", "6LfPTgMsAAAAAL_7gQW4z39H_WSv66KGr5uXiUT1");



$defaultLang = 'lv';

$languages = [
    'lv',
    'ru',
    'en',
];
$fullLanguages = [
    'lv' => 'Latviešu',
    'ru' => 'Русский',
    'en' => 'English',
];

$imageSizes = [
    "1" => [
        "w" => 500,
        "h" => 500,
    ],
    "2" => [
        "w" => 245,
        "h" => 245,
    ],
    "3" => [
        "w" => 90,
        "h" => 90,
    ],
];


define("HIDE_PRICES_FOR_NON_REGISTRED", false);