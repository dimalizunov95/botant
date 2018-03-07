<?php
define('CRON', dirname(__FILE__));
define('ROOT', CRON . '/../');

include(CRON . "/../custom/Database.php");
include(CRON . "/../Antpark.php");
include(CRON . "/../custom/Burse.php");
include(CRON . "/ParseCurrencies.php");

$parse = new \cron\ParseCurrencies();
$parse->insertCurrencies();

die;