<?php
$timeDiff = 3;
list($currHour, $currMinute, $weekDay) = explode(':', date('G:i:w'));
$currHour += $timeDiff;

if ($currHour >= 23 || $currHour <= 7) {
    exit();
}
file_get_contents('https://bot-ant.herokuapp.com/cron/pushups.php');
