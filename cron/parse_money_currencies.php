<?php
define('CRON', dirname(__FILE__));
define('ROOT', CRON . '/../');

include(CRON . "/../custom/Database.php");
include(CRON . "/../custom/Burse.php");
include(CRON . "/ParseCurrencies.php");

$all_banks = file_get_contents("http://resources.finance.ua/ru/public/currency-cash.json");
$all_banks = json_decode($all_banks, true);
$courses = [];

$insert_query = '';

foreach( $all_banks['organizations'] as $bank ) {
    if ( $bank['title'] == 'ПриватБанк' ) {
        $courses['banks']['privatbank'] = $bank['currencies'];
        foreach ($bank['currencies'] as $currency_symbol => $currency_info) {
            $insert_query .= " ('privatbank', '{$currency_symbol}', 'ask', '{$currency_info['ask']}'),";
            $insert_query .= " ('privatbank', '{$currency_symbol}', 'bid', '{$currency_info['bid']}'),";
        }
    }
}

$nbu = file_get_contents("https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json");
$nbu = json_decode($nbu, true);

foreach ($nbu as $course) {
    if ($course['cc'] == "USD" || $course['cc'] == "EUR" || $course['cc'] == "RUB") {
        $courses['nbu'][$course['cc']] = $course['rate'];
        $insert_query .= " ('nbu', '{$course['cc']}', 'both', '{$course['rate']}'),";
    }
}

$insert_query = rtrim($insert_query, ',');
$insert_query = "INSERT INTO money_currency (bank, name, ask_or_bid, price) VALUES" . $insert_query;
$insert_query .= " ON DUPLICATE KEY UPDATE
                price=VALUES(price);";
$x = 5;

$db = new \Custom\Database();
$db->queryToInsert(
    $insert_query
);

/*echo '<pre>';
print_r($courses);
echo '</pre>';*/
