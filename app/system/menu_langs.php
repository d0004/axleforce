<?php

$language = \_class\Registry::load('lang');

switch($language){
    case "lv":
        define('HOME_PAGE_LANG', 'Galvena');
        define('ABOUT_PAGE_LANG', 'Par mums');
        define('CONTACTS_PAGE_LANG', 'Kontakti');
        break;
    case "en":
        define('HOME_PAGE_LANG', 'Home page');
        define('ABOUT_PAGE_LANG', 'About us');
        define('CONTACTS_PAGE_LANG', 'Contacts');
        break;
    case "ru":
        define('HOME_PAGE_LANG', 'Главная');
        define('ABOUT_PAGE_LANG', 'О нас');
        define('CONTACTS_PAGE_LANG', 'Контакты');
        break;
}