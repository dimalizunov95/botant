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

//$token = "471628587:AAF78910ArSMCFiCeBWdYALmiGkkoe_8YDw";
$token = "523481115:AAFaHghK59yFQzLIF2lWDUpYtfhtMoGzY3k";
//$token = "";
$botan = 'ee17779d-4b22-4dcc-ac0a-95d3f4500fd6';

try {
    $bot = new \Custom\Bot($token, $botan);
    $bot->run();
} catch (\TelegramBot\Api\Exception $e) {
    $error = $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile();
}
