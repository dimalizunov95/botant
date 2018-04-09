<?php
define('CRON', dirname(__FILE__));
define('ROOT', dirname(__FILE__) . '/..');

header('Content-Type: text/html; charset=utf-8');
// подрубаем API
include("../vendor/autoload.php");
require("../autoloader.php");

$autoloader = new ClassAutoloader();
require '../Antpark.php';

$bot = new \Custom\Bot(Antpark::getInstance()->getToken());

$keyboard = [
    [
        ['callback_data' => 'pushups_done', 'text' => 'Я отжался!'],
    ]
];
$keyboard = $bot->createInlineKeyboard($keyboard);

$result = $bot->sendMsg(
    Antpark::getInstance()->getTestCryptoChatId(),
    'Пора отжиматься!',
    false,
    null,
    null,
    $keyboard
);
$currentTime = time();

$database = Antpark::getInstance()->Db();
$database->queryToInsert(
    "INSERT INTO pushups_event (message_id, time) VALUES ('{$result->getMessageId()}', '{$currentTime}')"
);