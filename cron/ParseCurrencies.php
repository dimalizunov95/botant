<?php

namespace cron;

use Custom\Burse;
use Custom\Database;

class ParseCurrencies
{

    public $proxy_servers = [
        "54.183.103.95:3128",
        "138.197.40.166:3128",
        "52.170.219.211:3128",
        "24.245.101.37:48678",
        "24.245.101.125:48678",
        "24.245.101.177:48678",
        "184.95.48.98:3128",

        /* Работают, но медленно */
        "107.129.112.166:3128",
        "96.71.40.129:3128",
        "35.196.91.42:3128",
    ];

    protected $database;

    public function __construct() {
        $this->database = \Antpark::getInstance()->Db();
    }

    public function getAllCurrenciesToParse() {
        /*$all_currencies_to_parse = $this->database->queryToSelect(
            "SELECT * FROM crypto_currencies"
        );*/
        $currencies = $this->database->queryToSelect(
            "SELECT cryptocurrency_info.id AS info_id, cryptocurrency_info.value_to_usd, cryptocurrency_info.value_to_btc, cryptocurrency_info.currency_symbol, all_cryptocurrency.id AS currency_id, all_cryptocurrency.name AS currency_name, all_cryptocurrency.code AS currency_code, burse.id AS burse_id, burse.name AS burse_name, burse.code AS burse_code
            FROM cryptocurrency_info
            LEFT JOIN all_cryptocurrency ON cryptocurrency_info.currency_id = all_cryptocurrency.id
            LEFT JOIN burse ON cryptocurrency_info.burse_id = burse.id"
        );
        $currencies_to_parse = [];
        foreach ($currencies as $currency) {
            $currencies_to_parse[$currency['burse_code']][$currency['currency_symbol']] = $currency;
        }
        return $currencies_to_parse;
    }

    /*
     * Вернёт все-все валюты, которые есть на соответствующей АПИ
     */
    public function parseCoinMarketCap() {
        $url = "https://api.coinmarketcap.com/v1/ticker/?limit=0";
        $json = file_get_contents($url);
        $info = json_decode($json, true);
        return $info;
    }

    /*
     * Вернёт все-все валюты, которые есть на соответствующей АПИ
     * Хитрый полоникс не даёт парсить валюты, приходится использовать прокси
     * в цикле проходимся по всем забитым прокси, если один не получился - идём к следующему
     */
    public function parsePoloniex() {

        $sFile = file_get_contents("https://poloniex.com/public?command=returnTicker");
        $result = json_decode($sFile, true);
        if ($result !== false && !is_null($result)) {
            return $result;
        }
        return false;

//        for local where need proxy
        /*foreach ($this->proxy_servers as $proxy) {
            $aContext = array(
                'http' => array(
                    'proxy' => $proxy,
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $sFile = file_get_contents("https://poloniex.com/public?command=returnTicker", False, $cxContext);
            $result = json_decode($sFile, true);
            if ($result !== false && !is_null($result)) {
                return $result;
                // шоб наверняка
                break;
            }
        }*/
    }

    /*
     * Принимает 2 параметра - все валюты (получаем из другого метода) и
     * на какую валюту нужно перевести. Приходится так делать, т.к.
     * в АПИ нет метода, с помощью которого можно получить только конкретные валюты,
     * тоько все
     */
    public function getMarketCupCurrencyFromAll($all_currencies, $from_currency) {
        foreach($all_currencies as $currency) {
            if ($currency['id'] == $from_currency) {
                $price['to_usd'] = $currency['price_usd'];
                $price['to_btc'] = $currency['price_btc'];
                return $price;
                //чтоб наверняка
                break;
            }
        }
        return null;
    }

    /*
     * Принимает 2 параметра - все валюты (получаем из другого метода) и
     * на какую валюту нужно перевести. Приходится так делать, т.к.
     * в АПИ нет метода, с помощью которого можно получить только конкретные валюты,
     * тоько все
     */
    public function getBitstamp($all_currencies_to_parse) {
        $parse_link = "https://www.bitstamp.net/api/v2/ticker/";
        $result = [];
        foreach($all_currencies_to_parse as $currency) {
            $parse_link_to_usd = $parse_link . $currency['currency_symbol'] . 'usd';
            $parse_to_usd = file_get_contents($parse_link_to_usd);
            $parse_to_usd = json_decode($parse_to_usd, true);

            if ($currency['currency_symbol'] == 'btc') {
                $parse_to_btc['last'] = 1;
            } else {
                $parse_link_to_btc = $parse_link . $currency['currency_symbol'] . 'btc';
                $parse_to_btc = file_get_contents($parse_link_to_btc);
                $parse_to_btc = json_decode($parse_to_btc, true);
            }

            $result[$currency['currency_symbol']]['to_usd'] = $parse_to_usd['last'];
            $result[$currency['currency_symbol']]['to_btc'] = $parse_to_btc['last'];
        }
        return $result;
    }

    /*
     * функции нужно передать запрос с парами валют, которые нужно получить такого вида:
     * tBTCUSD,tETHBTC
     * что значит t - описал ниже
     * Вернёт массив валют в таком виде:
     * [
     *      'пара валют' => 'значение'
     *      'tBTCUSD' => '12345'
     * ]
     * t значит trade, торговля, просто не обращать на неё внимание
     */
    public function parseBitfenix($all_db_currencies) {

        $query = $this->prepareQueryForBitfenixParse($all_db_currencies);

        $url = "https://api.bitfinex.com/v2/tickers?symbols=" . $query;
        $result = file_get_contents($url);
        $result = json_decode($result, true);

        $to_return = [];
        foreach ($result as $row) {
            if (strpos($row[7], 'E')) {
                $formatted_number = number_format($row[7], 15);
                $to_return[$row[0]] = rtrim($formatted_number, 0);
            } else {
                $to_return[$row[0]] = $row[7];
            }
        }
        return $to_return;
    }

    public function prepareQueryForBitfenixParse($all_db_currencies) {
        $query_to_parse_bitfenix = '';

        foreach ($all_db_currencies as $currency) {

            $currency['currency_symbol'] = strtoupper($currency['currency_symbol']);
            $query_to_parse_bitfenix .= "t{$currency['currency_symbol']}USD,t{$currency['currency_symbol']}BTC,";

            /*if (!is_null($currency['bitfenix_currency_name'])) {
                $currency['bitfenix_currency_name'] = strtoupper($currency['bitfenix_currency_name']);
                $query_to_parse_bitfenix .= "t{$currency['bitfenix_currency_name']}USD,t{$currency['bitfenix_currency_name']}BTC,";
            }*/
        }

        return $query_to_parse_bitfenix;
    }

    public function prepareQueriesForAllCurrenciesForDb() {
        $current_time = time();

        $all_currencies_to_parse = $this->getAllCurrenciesToParse();
        $all_coinmarketcap_currencies = $this->parseCoinMarketCap();
        $all_poloniex_currencies = $this->parsePoloniex();
        $all_needed_currencies_bitfenix = $this->parseBitfenix($all_currencies_to_parse['btfnx']);

        $query_bitfenix = '';
        $query_poloniex = '';
        $query_coinmarketcap = '';
        $query_bitstamp = '';

        $burse = new Burse();
        /*
         * code = burseName
         * btfnx = Bitfenix
         * cmc = coinmarketcap
         * plnx = Poloniex
         * bitstamp = Bitstamp
         */
        $active_burses = $burse->getAllActiveBurses();

        /*
         * Bitfenix
         */
        if (array_key_exists('btfnx', $active_burses)) {
            foreach ($all_currencies_to_parse['btfnx'] as $currency) {

                $currency['currency_symbol'] = strtoupper($currency['currency_symbol']);

                $price['to_usd'] = $all_needed_currencies_bitfenix["t{$currency['currency_symbol']}USD"];
                if ($currency['currency_symbol'] == 'BTC') {
                    $price['to_btc'] = 1;
                } else {
                    $price['to_btc'] = $all_needed_currencies_bitfenix["t{$currency['currency_symbol']}BTC"];
                }


                $query_bitfenix .= " ('{$currency['currency_id']}', '{$currency['burse_id']}', '{$price['to_usd']}', '{$price['to_btc']}', '{$currency['currency_symbol']}'), ";

            }
        }

        /*
         * Poloniex
         */
        if (array_key_exists('plnx', $active_burses)) {
            foreach ($all_currencies_to_parse['plnx'] as $currency) {

                $poloniex_currency_name = strtoupper($currency['currency_symbol']);
                // Если валюты нет к доллару, то считаем через биткоин
                if (!isset($all_poloniex_currencies['USDT_' . $poloniex_currency_name])) {
                    $price['to_usd'] = 0;
                } else {
                    $price['to_usd'] = $all_poloniex_currencies['USDT_' . $poloniex_currency_name]['last'];
                }
                // Если пытается привести от биткоина к биткоину, то просто пишем "=1"
                if ($poloniex_currency_name == 'BTC') {
                    $price['to_btc'] = 1;
                } else {
                    $price['to_btc'] = $all_poloniex_currencies['BTC_' . $poloniex_currency_name]['last'];
                }

                $query_poloniex .= " ('{$currency['currency_id']}', '{$currency['burse_id']}', '{$price['to_usd']}', '{$price['to_btc']}', '{$currency['currency_symbol']}'), ";

            }
        }

        /*
         * Coinmarketcap
         */
        if (array_key_exists('cmc', $active_burses)) {
            foreach ($all_currencies_to_parse['cmc'] as $currency) {

                $price = $this->getMarketCupCurrencyFromAll(
                    $all_coinmarketcap_currencies,
                    $currency['currency_symbol']
                );
                $query_coinmarketcap .= " ('{$currency['currency_id']}', '{$currency['burse_id']}', '{$price['to_usd']}', '{$price['to_btc']}', '{$currency['currency_symbol']}'), ";
            }
        }

        /*
         * Bitstamp
         */
        if (array_key_exists('bitstamp', $active_burses)) {
            $parse_link = "https://www.bitstamp.net/api/v2/ticker/";
            $result = [];
            foreach ($all_currencies_to_parse['bitstamp'] as $currency) {
                $parse_link_to_usd = $parse_link . $currency['currency_symbol'] . 'usd';
                $parse_to_usd = file_get_contents($parse_link_to_usd);
                $parse_to_usd = json_decode($parse_to_usd, true);

                if ($currency['currency_symbol'] == 'btc') {
                    $parse_to_btc['last'] = 1;
                } else {
                    $parse_link_to_btc = $parse_link . $currency['currency_symbol'] . 'btc';
                    $parse_to_btc = file_get_contents($parse_link_to_btc);
                    $parse_to_btc = json_decode($parse_to_btc, true);
                }

                $query_bitstamp .= " ('{$currency['currency_id']}', '{$currency['burse_id']}', '{$parse_to_usd['last']}', '{$parse_to_btc['last']}', '{$currency['currency_symbol']}'), ";
            }
        }

        $summary_query = rtrim($query_poloniex . $query_bitfenix . $query_coinmarketcap . $query_bitstamp, ', ');

        $to_return = "INSERT INTO cryptocurrency_info (currency_id, burse_id, value_to_usd, value_to_btc, currency_symbol) VALUES" . $summary_query;

        return $to_return;
    }

    public function insertCurrencies() {

        $query = $this->prepareQueriesForAllCurrenciesForDb();
        $query .= ' ON DUPLICATE KEY UPDATE
                value_to_usd=VALUES(value_to_usd),
                value_to_btc=VALUES(value_to_btc);';
        $this->database->queryToInsert(
            $query
        );
    }

}