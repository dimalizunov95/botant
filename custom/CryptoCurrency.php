<?php

namespace Custom;

//use TelegramBot\Api\Client;

/**
 * Class CryptoCurrency
 *
 * Класс для кнопки "топовые криптовалюты"
 *
 * @package TelegramBot\Api
 */
class CryptoCurrency
{

    // Сколько валют будет выводить в топ-списке
    protected $num_currencies_to_return = 10;

    public function parseCurrencies() {

        $url = 'https://api.coinmarketcap.com/v1/ticker/?limit=0';
        $json = file_get_contents($url);
        $json = json_decode($json, true);

        $new_json = [];
        foreach ($json as $row) {
            if ($row['market_cap_usd'] > 500000000) {
                $new_json[] = $row;
            }
        }
        return $new_json;

    }

    public function sortCurrenciesByRaw($sort_raw) {
        $db = $this->parseCurrencies();

        $sort = [];

        foreach ($db as $key => $currency_info) {
            $sort[$key] = $currency_info[$sort_raw];
        }

        array_multisort($sort, SORT_DESC, $db);

        $to_return = [];
        for ($i = 0; $i < $this->num_currencies_to_return; $i++) {
            $to_return[$i] = $db[$i];
        }

        return $to_return;

    }

    public function sortCurrenciesByRawAsc($sort_raw) {
        $db = $this->parseCurrencies();

        $sort = [];

        foreach ($db as $key => $currency_info) {
            $sort[$key] = $currency_info[$sort_raw];
        }

        array_multisort($sort, SORT_ASC, $db);

        $to_return = [];
        for ($i = 0; $i < $this->num_currencies_to_return; $i++) {
            $to_return[$i] = $db[$i];
        }

        return $to_return;

    }

    public function sortCurrenciesByChangeDayAsc($sort_raw) {
        $db = $this->parseCurrencies();

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
            if (count($to_return) == $this->num_currencies_to_return) {
                break;
            }

        }

        return $to_return;

    }

    public function sortCurrenciesByChangeWeekAsc($sort_raw) {
        $db = $this->parseCurrencies();

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
            if (count($to_return) == $this->num_currencies_to_return) {
                break;
            }

        }

        return $to_return;

    }

}
