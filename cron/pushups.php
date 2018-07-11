<?php
define('CRON', dirname(__FILE__));
define('ROOT', dirname(__FILE__) . '/..');

header('Content-Type: text/html; charset=utf-8');
// подрубаем API
include("../vendor/autoload.php");
require("../autoloader.php");

$autoloader = new ClassAutoloader();
require '../Antpark.php';

$scheduler = new \Custom\Scheduler();
$scheduler->sendMessagesToChats();