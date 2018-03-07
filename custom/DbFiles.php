<?php

namespace Custom;

//use TelegramBot\Api\Client;

/**
 * Class BotApi
 *
 * @package TelegramBot\Api
 */
class DbFiles
{


    /**
     * Check whether return associative array
     *
     * @var bool
     */
    protected $db_name;


    /**
     * Constructor
     *
     * @param string $token Telegram Bot API token
     * @param string|null $trackerToken Yandex AppMetrica application api_key
     */
    public function __construct($db_name)
    {
        $this->db_name = $db_name;
        /*$this->curl = curl_init();
        $this->token = $token;

        if ($trackerToken) {
            $this->tracker = new Botan($trackerToken);
        }*/
    }

    public function openFileToArray() {
        $file = $this->db_name;
        // Открываем файл для получения существующего содержимого
        $current = file_get_contents($file);
        $db = json_decode($current, true);
        return $db;
    }

    public function arrayToDbFile(array $arr) {
        $db = json_encode($arr);
        $result = file_put_contents($this->db_name, $db);
        return $result;
    }

    public function returnNum() {
        $x = 4123;

        return $x;
    }

    public function parseCurrencies() {
        $curr_file = $this->openFileToArray();
        $last_update = isset($curr_file['updated_at']) ? $curr_file['updated_at'] : 0;
        $current_time = time();
        if ( ($current_time - $last_update) > 600 ) {
            $url = 'https://api.coinmarketcap.com/v1/ticker/?limit=0';
            $json = file_get_contents($url);
            $json = json_decode($json, true);

            $new_json = [];
            foreach ($json as $row) {
                if ($row['market_cap_usd'] > 500000000) {
                    $new_json[] = $row;
                }
            }

            $current_time = time();
            $new_json['updated_at'] = $current_time;

            $this->arrayToDbFile($new_json);
            $curr_file = $new_json;
        }

        return $curr_file;

    }

    public function sortCurrenciesByRaw($sort_raw) {
        $db = $this->parseCurrencies();

        array_pop($db);

        $sort = [];

        foreach ($db as $key => $currency_info) {
            $sort[$key] = $currency_info[$sort_raw];
        }

        array_multisort($sort, SORT_DESC, $db);

        $to_return = [];
        for ($i = 0; $i < 10; $i++) {
            $to_return[$i] = $db[$i];
        }

        return $to_return;

    }

    public function sortCurrenciesByRawAsc($sort_raw) {
        $db = $this->parseCurrencies();

        array_pop($db);

        $sort = [];

        foreach ($db as $key => $currency_info) {
            $sort[$key] = $currency_info[$sort_raw];
        }

        array_multisort($sort, SORT_ASC, $db);

        $to_return = [];
        for ($i = 0; $i < 10; $i++) {
            $to_return[$i] = $db[$i];
        }

        return $to_return;

    }

    public function sortCurrenciesByChangeDayAsc($sort_raw) {
        $db = $this->parseCurrencies();

        array_pop($db);

        $sort = [];

        foreach ($db as $key => $currency_info) {
            $sort[$key] = $currency_info[$sort_raw];
        }

        array_multisort($sort, SORT_ASC, $db);

        $to_return = [];
        for ($i = 0; $i < 150; $i++) {
            if ($db[$i]['percent_change_24h'] != null) {
                $to_return[$i] = $db[$i];
            }
            if (count($to_return) == 10) {
                break;
            }

        }

        return $to_return;

    }

    public function sortCurrenciesByChangeWeekAsc($sort_raw) {
        $db = $this->parseCurrencies();

        array_pop($db);

        $sort = [];

        foreach ($db as $key => $currency_info) {
            $sort[$key] = $currency_info[$sort_raw];
        }

        array_multisort($sort, SORT_ASC, $db);

        $to_return = [];
        for ($i = 0; $i < 150; $i++) {
            if ($db[$i]['percent_change_7d'] != null) {
                $to_return[$i] = $db[$i];
            }
            if (count($to_return) == 10) {
                break;
            }

        }

        return $to_return;

    }

    /*public function getBestCurrenciesByUsd() {
        $db = $this->parseCurrencies();

        array_pop($db);

        $price_usd = [];

        foreach ($db as $key => $currency_info) {
            $name[$key] = $currency_info['name'];
            $price_usd[$key] = $currency_info['price_usd'];
        }

        array_multisort($price_usd, SORT_DESC, $db);

        $to_return = [];
        for ($i = 0; $i < 10; $i++) {
            $to_return[$i]['name'] = $db[$i]['name'];
            $to_return[$i]['symbol'] = $db[$i]['symbol'];
            $to_return[$i]['price_usd'] = $db[$i]['price_usd'];
        }

        return $to_return;

    }*/

}
