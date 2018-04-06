<?php
define('ROOT', dirname(__FILE__));

header('Content-Type: text/html; charset=utf-8');
// подрубаем API
include("vendor/autoload.php");
require("autoloader.php");

$autoloader = new ClassAutoloader();
require 'Antpark.php';

// дебаг
if(true){
    error_reporting(E_ALL & ~(E_NOTICE | E_USER_NOTICE | E_DEPRECATED));
    ini_set('display_errors', 1);
}

$token = "529920509:AAEteMXxoTlkMvgbWYqkwFAlC9hfcShCvHM";
//$token = "";
//$botan = '';

try {
    $bot = new \Custom\Bot($token);
    $bot->run();
} catch (\TelegramBot\Api\Exception $e) {
    $error = $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile();
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=399527521&text=$error");
}
