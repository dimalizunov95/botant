<?php

namespace Custom;

/**
 * Class BotApi
 *
 * @package TelegramBot\Api
 */
class Parse
{

    protected $database;

    protected $proxy_servers = [
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

    public function __construct()
    {
        try {
            $this->database = \Antpark::getInstance()->Db();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }


    }

    public function getExchangeRateBitfenix($from, $to) {
        $url = 'https://api.bitfinex.com/v1/pubticker/';
        $url_to_parse = $url . $from . $to;
        /*$currency_info = file_get_contents($url_to_parse);
        $currency_info = $this->jsonToArray($currency_info);*/
        $currency_info = $this->arrayFromParsedJson($url_to_parse);
        $price = isset($currency_info['last_price']) ? $currency_info['last_price'] : false;
        return $price;
    }

    public function getExchangeRateCoinmarketcap($from, $to) {
        $url = "https://api.coinmarketcap.com/v1/ticker/";
        $url_to_parse = $url . $from;
        /*$currency_info = file_get_contents($url_to_parse);
        $currency_info = $this->jsonToArray($currency_info);*/
        $currency_info = $this->arrayFromParsedJson($url_to_parse);
        $currency_info = $currency_info[0];
        if ($to == 'usd') {
            $price = $currency_info['price_usd'];
        } else {
            $price = $currency_info['price_btc'];
        }
        return $price;
    }

    public function getExchangeRateCoinmarketcapWithConvertation($from, $to) {
        $url = "https://api.coinmarketcap.com/v1/ticker/?convert=";
        $url_to_parse = $url . $to;
        $currency_info = $this->arrayFromParsedJson($url_to_parse);
        foreach ($currency_info as $currency) {
            if ($currency['symbol'] == strtoupper($from)) {
                $price = $currency['price_' . $to];
                return $price;
            }
        }
        return false;
    }

    public function getExchangeRatePoloniex($pair = "ALL") {

        $prices = $this->arrayFromParsedJsonViaProxy("https://poloniex.com/public?command=returnTicker");

        $pair = strtoupper($pair);
        if($pair == "ALL"){
            return $prices;
        }else{
            $pair = strtoupper($pair);
            if(isset($prices[$pair])){
                return $prices[$pair]['last'];
            }else{
                return false;
            }
        }

    }

    public function getExchangeRateBitstamp($from, $to) {
        $link_to_parse = "https://www.bitstamp.net/api/v2/ticker/" . $from . $to;
        $parse_link = $this->arrayFromParsedJson($link_to_parse);
//        $parse_link['error']
        if (array_key_exists('last', $parse_link)) {
            return $parse_link['last'];
        }
        return false;
    }

    /*
     * Error codes:
     * 1 - couldn't find info on the site
     * 2 - site's unreachable atm
     */
    public function arrayFromParsedJson($link_to_parse) {
        try {
            $content = file_get_contents($link_to_parse);

            // Почему-то ничего не получили
            if ($content === false) {
                return ['error' => '1'];
            }

            $result = json_decode($content, true);
            return $result;
        } catch (\Exception $e) {
            // Сервис недоступен
            return ['error' => '2'];
        }
    }

    public function arrayFromParsedJsonViaProxy($link_to_parse) {
        foreach ($this->proxy_servers as $proxy) {
            $aContext = array(
                'http' => array(
                    'proxy' => $proxy,
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $sFile = file_get_contents($link_to_parse, False, $cxContext);
            $result = json_decode($sFile, true);
            if ($result !== false && !is_null($result)) {
                return $result;
                // шоб наверняка
                break;
            }
        }
    }

    public function getMoneyCurrencyRates($currency_to) {
        $rates = $this->database->queryToSelect(
            "SELECT * FROM money_currency WHERE name = '{$currency_to}'"
        );
        foreach ($rates as $rate) {
            if ($rate['bank'] == 'nbu') {
                $result['nbu'] = $rate['price'];
            }
            if ($rate['bank'] == 'privatbank') {
                if ($rate['ask_or_bid'] == 'ask') {
                    $result[$rate['bank']]['ask'] = $rate['price'];
                }
                if ($rate['ask_or_bid'] == 'bid') {
                    $result[$rate['bank']]['bid'] = $rate['price'];
                }
            }
        }
        return $result;
    }

    public function moneyCurrencyParse() {
        $all_banks = file_get_contents("http://resources.finance.ua/ru/public/currency-cash.json");
        $all_banks = json_decode($all_banks, true);
        $courses = [];

        foreach( $all_banks['organizations'] as $bank ) {
            if ( $bank['title'] == 'ПриватБанк' ) {
                $courses['banks']['privatbank'] = $bank['currencies'];
            }
            /*if ( $bank['title'] == 'Ощадбанк' ) {
                $courses['banks']['oshadbank'] = $bank['currencies'];
            }*/
        }

        $nbu = file_get_contents("https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json");
        $nbu = json_decode($nbu, true);

        foreach ($nbu as $course) {
            if ($course['cc'] == "USD" || $course['cc'] == "EUR" || $course['cc'] == "RUB") {
                $courses['nbu'][$course['cc']] = $course['rate'];
            }
        }
        return $courses;
    }

    public function parseAllCryptoCurrencies($from_currency, $to_currency) {
        $price_bitfenix = $this->getExchangeRateBitfenix($from_currency, $to_currency);
        $price_coinmarcetcap = $this->getExchangeRateCoinmarketcapWithConvertation($from_currency, $to_currency);
//        $price_poloniex = $this->getExchangeRatePoloniex($from_currency . '_' . $to_currency);
        $price_bitstamp = $this->getExchangeRateBitstamp($from_currency, $to_currency);

        return [
            'price_bitfenix' => $price_bitfenix,
            'price_coinmarcetcap' => $price_coinmarcetcap,
//            'price_poloniex' => $price_poloniex,
            'price_bitstamp' => $price_bitstamp,
        ];
    }

    public function getBoobsId() {
        $link_obj = $this->arrayFromParsedJson("http://api.oboobs.ru/noise/1");
        /*$link = file_get_contents("http://api.oboobs.ru/noise/1");
        $link_obj = $this->jsonToArray($link);*/

        preg_match("/\/(\d+)\.[a-zA-Z]{3,4}$/ ", $link_obj[0]['preview'], $preg_result);
        $boobs_id = $preg_result[1];
//        $pic = 'http://media.oboobs.ru/' . $link;
        return $boobs_id;
    }

    public function getRidOfDislikedBoobs() {
        $boobs_id = $this->getBoobsId();

        $votes_to_image = $this->database->queryToSelect("
            SELECT
                COUNT(CASE WHEN liked_or_not = 1 AND boobs_image_id = '{$boobs_id}' THEN 1 END) AS likes,
                COUNT(CASE WHEN liked_or_not = 0 AND boobs_image_id = '{$boobs_id}' THEN 1 END) AS dislikes
            FROM boobs_likes
        ");
        $likes_to_image = $votes_to_image[0]['likes'];
        $dislikes_to_image = $votes_to_image[0]['dislikes'];

        $rates_difference = $dislikes_to_image - $likes_to_image;

        if ($rates_difference >= 2) {
            return false;
        } else {
            return [
                'boobs_id' => $boobs_id,
                'likes_to_image' => $likes_to_image,
                'dislikes_to_image' => $dislikes_to_image,
            ];
        }
    }

    public function getLinkToBoobs($boobs_id) {
        $pic = 'http://media.oboobs.ru/noise_preview/' . $boobs_id . '.jpg';
        return $pic;
    }

    public function getBoobs() {
        $boobs_array = $this->getRidOfDislikedBoobs();

        if (!$boobs_array) {
            return [
                'error' => 'bad img'
            ];
        }

        $pic_link = $this->getLinkToBoobs($boobs_array['boobs_id']);
        return [
            'boobs_id' => $boobs_array['boobs_id'],
            'likes_to_image' => $boobs_array['likes_to_image'],
            'dislikes_to_image' => $boobs_array['dislikes_to_image'],
            'pic_link' => $pic_link
        ];
    }

}
