<?php

namespace Custom;

use cron\ParseCurrencies;
use TelegramBot\Api\Client;
use TelegramBot\Api\Exception;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use Custom\DbFiles;
use Custom\Database;
use TelegramBot\Api\Botan;

/**
 * Class BotApi
 *
 * @package TelegramBot\Api
 */
class Bot
{


    /**
     * Check whether return associative array
     *
     * @var bool
     */
    protected $returnArray = true;

    /**
     *
     */
    protected $bot;

    protected $database;

    protected $crypto_currency;

    protected $botanApi;


    /**
     * Constructor
     *
     * @param string $token Telegram Bot API token
     * @param string|null $trackerToken Yandex AppMetrica application api_key
     */
    public function __construct($token, $trackerToken = null) {
        $this->database = \Antpark::getInstance()->Db();
        $this->bot = new Client($token, $trackerToken);
        $this->crypto_currency = new CryptoCurrency();
        if (!is_null($trackerToken)) {
            $this->botanApi = new Botan($trackerToken);
        }
    }

    public function checkBotForRegistration() {
        if (!file_exists("registered.trigger")) {
            /**
             * файл registered.trigger будет создаваться после регистрации бота.
             * если этого файла нет значит бот не зарегистрирован
             */

            // URl текущей страницы
            $page_url = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
            $cert_pem = "/etc/ssl/certs/server.pem";
            $result = $this->bot->setWebhook($page_url, $cert_pem);
            if ($result) {
                file_put_contents("registered.trigger", time()); // создаем файл дабы прекратить повторные регистрации
            } else die("ошибка регистрации");
        }
    }

    // Тут собраны все команды бота
    protected function commands() {
        $bot = $this->bot;
        $database = $this->database;
        $crypto_currency = $this->crypto_currency;

        $bot->command('start', function ($message) use ($bot, $database, $crypto_currency) {

            $cid = $message->getChat()->getId();
            $user_from_id = $message->getFrom()->getId();
            $user = new User(
                $user_from_id,
                $message->getFrom()->getFirstName(),
                $message->getFrom()->getLastName()
            );

            /*$database->registerUser(
                $user_from_id,
                $message->getFrom()->getFirstName(),
                $message->getFrom()->getLastName()
            );*/
            $database->registerChat(
                $cid,
                $message->getChat()->getType()
            );

            $keyboard = new ReplyKeyboardMarkup(
                \Antpark::getInstance()->getMainKeyboard(),
                false, true
            );

            $answer = 'Привет. Я BitStatBot. Создан для того чтобы ты всегда получал актуальную и нужную информацию по крипто валюте.';
            $bot->sendMessage($cid, $answer, false, null, null, $keyboard, true);
        });

        $bot->command('currency', function ($message) use ($bot, $database, $crypto_currency) {
            $keyboard = new InlineKeyboardMarkup(
                [
                    [
                        ['callback_data' => '/EUR', 'text' => 'EUR'],
                        ['callback_data' => '/USD', 'text' => 'USD']
                    ],
                    [
                        ['callback_data' => '/UAH', 'text' => 'UAH'],
                        ['callback_data' => '/RUB', 'text' => 'RUB'],
                    ]
                ]
            );

            $bot->sendMessage($message->getChat()->getId(), "С какой валюты?", false, null, null, $keyboard);
        });

        // помощ
        $bot->command('help', function ($message) use ($bot, $database) {
            $answer = 'Команды:
        /help - помощ';
            $bot->sendMessage($message->getChat()->getId(), $answer);
        });

        $bot->command('sendnewbuttons', function ($message) use ($bot, $database) {

            $cid = $message->getChat()->getId();
            $user_telegram_id = $message->getFrom()->getId();
            $user = new User(
                $user_telegram_id,
                $message->getFrom()->getFirstName(),
                $message->getFrom()->getLastName()
            );

            if ($user->isAdmin()) {
                /*$cids = $database->queryToSelect(
                    "SELECT chat_id FROM all_chats"
                );*/

                $keyboard = new ReplyKeyboardMarkup(
                    \Antpark::getInstance()->getMainKeyboard(),
                    false, true
                );
                /*$cids[]['chat_id'] = -247271206;*/
                $cids[]['chat_id'] = 399527521;
                $sleep_count = 0;
                foreach ($cids as $cid) {
                    if ($sleep_count %5 == 0) {
                        sleep(2);
                    }
                    $cid = $cid['chat_id'];
                    try {
                        $bot->sendMessage($cid, 'Вот твоя клавиатура:', false, null, null, $keyboard, true);
//                        $bot->sendMessage($cid, 'После всех updates и доработак, спешим вам представить нового Bota, который поможет со всеми Crypto делами. Скорей добавляй @BitStatBot  в друзья и он порадует тебя.');
                    } catch (Exception $e) {}
                    $sleep_count++;
                }
            } else {
                $bot->sendMessage($cid, "Это доступно только админам");
            }
        });

        $bot->command("buttons", function ($message) use ($bot, $database) {
            $cid = $message->getChat()->getId();
            $keyboard = new ReplyKeyboardMarkup(
                \Antpark::getInstance()->getMainKeyboard(),
                false, true
            );

            $bot->sendMessage($cid, 'Вот твоя клавиатура: ', false, null, null, $keyboard);
        });

        $bot->command("test", function ($message) use ($bot, $database) {

            $user = new User(
                $message->getFrom()->getId(),
                $message->getFrom()->getFirstName(),
                $message->getFrom()->getLastName()
            );

            if ($user->isAdmin()) {
                $keyboard = new ReplyKeyboardMarkup(
                    \Antpark::getInstance()->getMainKeyboardTest(),
                    false, true
                );

                $bot->sendMessage($message->getChat()->getId(), 'test', false, null, null, $keyboard, true);
            }
        });

        $bot->command("ibutton", function ($message) use ($bot, $database) {
            $keyboard = new InlineKeyboardMarkup(
                [
                    [
                        ['callback_data' => 'data_test', 'text' => 'Answer'],
                        ['callback_data' => 'data_test2', 'text' => 'ОтветЪ']
                    ]
                ]
            );

            $bot->sendMessage($message->getChat()->getId(), "тест", false, null, null, $keyboard);
        });

    }

    protected function processingReplyButtons() {
        $bot = $this->bot;
        $database = $this->database;
        $crypto_currency = $this->crypto_currency;
        $botanApi = $this->botanApi;

        $bot->on(function ($Update) use ($bot, $database, $crypto_currency, $botanApi) {

            $message = $Update->getMessage();
//            $botanApi->track($message, 'ReplyButtons');
            if (!is_null($message)) {
                $mtext = $message->getText();
                $cid = $message->getChat()->getId();
                $user_from_id = $message->getFrom()->getId();
                $user = new User(
                    $user_from_id,
                    $message->getFrom()->getFirstName(),
                    $message->getFrom()->getLastName()
                );
                $chat_db_id = $database->registerChat(
                    $cid,
                    $message->getChat()->getType()
                );
            } else {
                $mtext = '';
                $cid = '';
                $user_from_id = '';
            }

            $current_time = time();

            if (strpos($mtext, "Курс криптовалют") !== false) {

                $botanApi->track($message, 'CryptocurrencyRate');
                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_usd', 'text' => 'К USD'],
                            ['callback_data' => 'crypto_exchange_rate_to_btc', 'text' => 'К BTC']
                        ]
                    ]
                );

                $bot->sendMessage($cid, "Курс криптовалют", false, null, null, $keyboard);

            } else if (strpos($mtext, "Калькулятор криптовалют") !== false) {

                $botanApi->track($message, 'CryptocurrencyCalculator');
                $message = 'Чтобы использовать калькулятор, напишите сообщение в таком формате:
сумма с какой валюты to на какую валюту.
Например: 2.53 eth to btc';
                $bot->sendMessage($cid, $message);

            } else if (strpos($mtext, "Арбитраж") !== false) {

                $botanApi->track($message, 'Arbitration');
                $message = 'Данная функция находится в разработке.';
                $bot->sendMessage($cid, $message);

            } else if (strpos($mtext, "Курс биткоина") !== false) {

                $json = file_get_contents('https://api.coinmarketcap.com/v1/ticker/?limit=1');
                $json = json_decode($json, true);
                $json = $json[0];
                $price = number_format($json['price_usd'], 2, '.', ' ');
                $message = $price . ' $';

                /*$db_row = $database->queryToSelect("SELECT * FROM currency LEFT JOIN currency_info "
                    . "ON currency.id = currency_info.currency_id WHERE currency.currency_id = "
                    . "'bitcoin' ORDER BY currency_info.last_updated DESC LIMIT 1");
                $last_bitcoin_row = $db_row[0];
                $price = number_format($last_bitcoin_row['price_usd'], 2, '.', ' ');
                $message = $price . ' $';*/

                $bot->sendMessage($cid, $message);

            } else if (strpos($mtext, "Курс валют") !== false) {

                $botanApi->track($message, 'CurrencyRate');

                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => '/money_convert_from_db_from_UAH', 'text' => 'К UAH'],
                            ['callback_data' => '/money_convert_another_directions', 'text' => 'Другие направления']
                        ],
                    ]
                );

                $bot->sendMessage($cid, "Выберите: ", false, null, null, $keyboard);
                /*$keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => '/money_convert_to_EUR', 'text' => 'EUR'],
                            ['callback_data' => '/money_convert_to_USD', 'text' => 'USD']
                        ],
                        [
                            ['callback_data' => '/money_convert_to_RUB', 'text' => 'RUB'],
                        ]
                    ]
                );

                $bot->sendMessage($message->getChat()->getId(), "На какую валюту?", false, null, null, $keyboard);*/

            } else if (strpos($mtext, "Топовые криптовалюты") !== false) {

                $botanApi->track($message, 'CryptocurrencyTop');

                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => "/top_by_rate_to_dollar", "text" => "Курсу к доллару"],
                            ['callback_data' => "/top_by_capitalization", "text" => "Капитализации"],
                        ],
                        [
                            ['callback_data' => "/top_by_daily_growth", "text" => "Суточному росту"],
                            ['callback_data' => "/top_by_fall", "text" => "Суточному падению"],
                        ],
                        [
                            ['callback_data' => "/top_by_weekly_growth", "text" => "Недельному росту"],
                            ['callback_data' => "/top_by_weekly_fall", "text" => "Недельному падению"],
                        ],
                        [
                            ['callback_data' => "/top_by_daily_volume", "text" => "Суточному обьему торгов"],
                        ],
                    ]
                );
                
                $bot->sendMessage($cid, "По какому признаку?: ", false, null, null, $keyboard);

            } else {

                if (/*isset($user) && $user->isAdmin()*/ true) {
                    // 150 btc to eth
                    // AMOUNT FROM to TO
                    $income_message = explode(" ", $mtext);
                    $amount = isset($income_message[0]) ? $income_message[0] : null;
                    $from_currency = isset($income_message[1]) ? $income_message[1] : null;
                    $to_currency = isset($income_message[3]) ? $income_message[3] : null;
                    $test = isset($income_message[4]) ? $income_message[4] : null;

                    // Если $test не равна null, то сообщение сильно длинное и нам не подходит
                    if (!is_null($test)) {
                        return;
                    }

                    $amount = floatval(str_replace(',', '.', $amount));

                    if (
                        !is_null($amount) &&
                        !is_null($from_currency) &&
                        !is_null($to_currency) &&
                        $amount != 0
                    ) {

                        $bot->sendChatAction(
                            $cid,
                            'typing'
                        );

                        $amount = strtolower($amount);
                        $from_currency = strtolower($from_currency);
                        $to_currency = strtolower($to_currency);

                        $parse = new Parse();
                        $all_prices = $parse->parseAllCryptoCurrencies($from_currency, $to_currency);

                        $price_bitfenix = $all_prices['price_bitfenix'] * $amount;
                        $price_coinmarcetcap = $all_prices['price_coinmarcetcap'] * $amount;
//                        $price_poloniex = $all_prices['price_poloniex'] * $amount;
                        $price_bitstamp = $all_prices['price_bitstamp'] * $amount;

                        // Если инфы по такой паре валют нет, то пытаемся найти инфу
                        // по обратной паре и пересчитать через это значение
                        if (
                            $price_bitfenix == 0 &&
                            $price_coinmarcetcap == 0 &&
//                            $price_poloniex == 0 &&
                            $price_bitstamp == 0
                        ) {
                            $all_prices = $parse->parseAllCryptoCurrencies($to_currency, $from_currency);
                            if ($all_prices['price_bitfenix'] !== false) {
                                $price_bitfenix = 1 / ($all_prices['price_bitfenix']) * $amount;
                                $price_bitfenix = rtrim( number_format($price_bitfenix, 15), 0 );
                            } else {
                                $price_bitfenix = 0;
                            }
                            if ($all_prices['price_coinmarcetcap'] !== false) {
                                $price_coinmarcetcap = 1 / ($all_prices['price_coinmarcetcap']) * $amount;
                                $price_coinmarcetcap = rtrim( number_format($price_coinmarcetcap, 15), 0 );
                            } else {
                                $price_coinmarcetcap = 0;
                            }
                            if ($all_prices['price_bitstamp'] !== false) {
                                $price_bitstamp = 1 / ($all_prices['price_bitstamp']) * $amount;
                                $price_bitstamp = rtrim( number_format($price_bitstamp, 15), 0 );
                            } else {
                                $price_bitstamp = 0;
                            }

//                        $price_poloniex = 1 / ($all_prices['price_poloniex']) * $amount;

                        }


                        $from_currency = strtoupper($from_currency);
                        $to_currency = strtoupper($to_currency);

                        $message =  '';
                        if ($price_bitfenix != 0) {
                            $message .= 'Bitfenix: ' . $price_bitfenix . ' ' . $to_currency . "\n\r";
                        }
                        if ($price_coinmarcetcap != 0) {
                            $message .= 'Coinmarketcap: ' . $price_coinmarcetcap . ' ' . $to_currency . "\n\r";
                        }
                        if ($price_bitstamp != 0) {
                            $message .= 'Bitstamp: ' . $price_bitstamp . ' ' . $to_currency . "\n\r";
                        }

                        if ($price_bitfenix != 0 || $price_coinmarcetcap != 0 || $price_bitstamp != 0) {
                            $message =  $amount . ' ' . $from_currency . ': ' . "\n\r" . $message;
                        } else {
                            $message = 'Нет информации по такой паре валют.';
                        }

                        $bot->sendMessage($cid, $message);
                    }
                }
            }

        }, function ($message) {
            return true; // когда тут true - команда проходит
        });

    }

    protected function processingInlineButtons() {
        $bot = $this->bot;
        $database = $this->database;
        $crypto_currency = $this->crypto_currency;

        $bot->on(function ($update) use ($bot, $database, $crypto_currency) {
            $callback = $update->getCallbackQuery();
            $message = $callback->getMessage();
            $bot->track($message, 'InlineButtons');
            $chatId = $message->getChat()->getId();
            $data = $callback->getData();
            $user_from_id = $callback->getFrom()->getId();
            $current_time = time();
            $message_id = $message->getMessageId();

            $user = new User(
                $user_from_id,
                $callback->getFrom()->getFirstName(),
                $callback->getFrom()->getLastName()
            );
            $chat_db_id = $database->registerChat(
                $chatId,
                $message->getChat()->getType()
            );

            if ($data == "data_test") {
                $bot->answerCallbackQuery($callback->getId(), "This is Ansver!", true);
            }
            if ($data == "data_test2") {
                $bot->sendMessage($chatId, "Это ответ!");
                $bot->answerCallbackQuery($callback->getId()); // можно отослать пустое, чтобы просто убрать "часики" на кнопке
            }

            if ($data == '/money_convert_another_directions') {
                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => '/money_convert_another_directions_from_USD', 'text' => 'USD'],
                            ['callback_data' => '/money_convert_another_directions_from_EUR', 'text' => 'EUR']
                        ],
                        [
                            ['callback_data' => '/money_convert_another_directions_from_RUB', 'text' => 'RUB'],
                            ['callback_data' => '/money_convert_another_directions_from_UAH', 'text' => 'UAH'],
                        ],
                        [
                            ['callback_data' => '/money_convert_another_directions_from_PLN', 'text' => 'PLN'],
                            ['callback_data' => '/money_convert_another_directions_from_GBP', 'text' => 'GBP'],
                        ],
                    ]
                );

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    "С какой валюты?",
                    null,
                    false,
                    $keyboard
                );
            }

            if (preg_match('/^\/money_convert_another_directions_from_[A-Z]{3}/', $data)) {
                $from_currency = str_replace('/money_convert_another_directions_from_', '', $data);

                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => '/money_convert_another_directions_from_' . $from_currency . '_to_USD', 'text' => 'USD'],
                            ['callback_data' => '/money_convert_another_directions_from_' . $from_currency . '_to_EUR', 'text' => 'EUR']
                        ],
                        [
                            ['callback_data' => '/money_convert_another_directions_from_' . $from_currency . '_to_RUB', 'text' => 'RUB'],
                            ['callback_data' => '/money_convert_another_directions_from_' . $from_currency . '_to_UAH', 'text' => 'UAH'],
                        ],
                        [
                            ['callback_data' => '/money_convert_another_directions_from_' . $from_currency . '_to_PLN', 'text' => 'PLN'],
                            ['callback_data' => '/money_convert_another_directions_from_' . $from_currency . '_to_GBP', 'text' => 'GBP'],
                        ],
                    ]
                );

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    "На какую валюту?",
                    null,
                    false,
                    $keyboard
                );
            }

            if (preg_match('/^\/money_convert_another_directions_from_[A-Z]{3}_to_[A-Z]{3}/', $data)) {
                preg_match_all('/[A-Z]{3}/', $data, $currency_info);

                $from_currency = $currency_info[0][0];
                $to_currency = $currency_info[0][1];
                $currency_to_parse = $from_currency . '_' . $to_currency;

                $link_to_parse = "https://free.currencyconverterapi.com/api/v5/convert?q={$currency_to_parse}&compact=y";
                $parse = new Parse();
                $parse_result = $parse->arrayFromParsedJson($link_to_parse);

                if (array_key_exists('error', $parse_result)) {
                    $text = 'Сервис в данный момент недоступен. Попробуйте позже или воспользуйтесь курсом к гривне.';
                } else {

                    $text = '1 ' . $from_currency . ' = ' . $parse_result[$currency_to_parse]['val'] . $to_currency;
                }

                $keyboard = new InlineKeyboardMarkup( [] );

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    $text,
                    null,
                    false,
                    $keyboard
                );
            }

            if ($data == "/money_convert_from_db_from_UAH") {
                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => '/money_convert_from_db_from_UAH_to_EUR', 'text' => 'EUR'],
                            ['callback_data' => '/money_convert_from_db_from_UAH_to_USD', 'text' => 'USD']
                        ],
                        [
                            ['callback_data' => '/money_convert_from_db_from_UAH_to_RUB', 'text' => 'RUB'],
                        ]
                    ]
                );

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    "На какую валюту?",
                    null,
                    false,
                    $keyboard
                );

            }

            if (preg_match('/^\/money_convert_from_db_from_UAH_to_[A-Z]{3}/', $data)) {
                $currency_to = str_replace("/money_convert_from_db_from_UAH_to_", '', $data);

                $keyboard = new InlineKeyboardMarkup([]);

                $parse = new Parse();

                $rates = $parse->getMoneyCurrencyRates($currency_to);


                $text = "1 $currency_to: \n\r";
                $text .= "Официальный курс НБУ: \n\r";
                $text .= $rates['nbu'] . " грн. \n\r \n\r";
                $text .= "По ПриватБанку: \n\r";
                $text .= "Покупка: {$rates['privatbank']['bid']} грн. \n\r";
                $text .= "Продажа: {$rates['privatbank']['ask']} грн.";

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    $text,
                    null,
                    false,
                    $keyboard
                );

                $bot->answerCallbackQuery($callback->getId()); // можно отослать пустое, чтобы просто убрать "часики" на кнопке
            }

            if ($data == "/top_by_rate_to_dollar") {
                // Курсу к доллару

                $currencies = $crypto_currency->sortCurrenciesByRaw('price_usd');
                $message = '';

                $rowNum = 1;
                foreach ($currencies as $currency) {
                    $currencySymbolInfo = explode(' ', $currency['symbol']);
                    $currencySymbol = array_pop($currencySymbolInfo);

                    $message .= $rowNum . ') ' . $currency['name'] . ' ';
                    $message .= implode(' ', $currencySymbolInfo) . '*' . $currencySymbol . '* ';
                    $message .= number_format($currency['price_usd'], 0, '.', ' ') . '$';
                    $message .= "\n\r";
                    $rowNum++;
                }
                $keyboard = new InlineKeyboardMarkup([]);

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    "Топовые валюты по курсу к доллару с капитализацией более 500 млн. долл.: \n\r" . $message,
                    'Markdown',
                    false,
                    $keyboard
                );

            }

            if ($data == "/top_by_capitalization") {
                // Капитализации
                $currencies = $crypto_currency->sortCurrenciesByRaw('market_cap_usd');
                $message = '';

                $rowNum = 1;
                foreach ($currencies as $currency) {
                    $currencySymbolInfo = explode(' ', $currency['symbol']);
                    $currencySymbol = array_pop($currencySymbolInfo);

                    $message .= $rowNum . ') ' . $currency['name'] . ' ';
                    $message .= implode(' ', $currencySymbolInfo) . '*' . $currencySymbol . '* ';
                    $message .= number_format($currency['market_cap_usd'], 0, '.', ' ') . '$';
                    $message .= "\n\r";
                    $rowNum++;
                }
                $keyboard = new InlineKeyboardMarkup([]);

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    "Топовые валюты по капитализации: \n\r" . $message,
                    'Markdown',
                    false,
                    $keyboard
                );
            }

            if ($data == "/top_by_daily_volume") {
                // Суточному объёму торгов
                $currencies = $crypto_currency->sortCurrenciesByRaw('24h_volume_usd');
                $message = '';

                $rowNum = 1;
                foreach ($currencies as $currency) {
                    $currencySymbolInfo = explode(' ', $currency['symbol']);
                    $currencySymbol = array_pop($currencySymbolInfo);

                    $message .= $rowNum . ') ' . $currency['name'] . ' ';
                    $message .= implode(' ', $currencySymbolInfo) . '*' . $currencySymbol . '* ';
                    $message .= number_format($currency['24h_volume_usd'], 0, '.', ' ') . '$';
                    $message .= "\n\r";
                    $rowNum++;
                }
                $keyboard = new InlineKeyboardMarkup([]);

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    "Топовые валюты по суточному объёму торгов с капитализацией более 500 млн. долл.: \n\r" . $message,
                    'Markdown',
                    false,
                    $keyboard
                );
            }

            if ($data == "/top_by_daily_growth") {
                // Суточному росту
                $currencies = $crypto_currency->sortCurrenciesByRaw('percent_change_24h');
                $message = '';

                $rowNum = 1;
                foreach ($currencies as $currency) {
                    if (floatval($currency['percent_change_24h']) > 0) {
                        $currencySymbolInfo = explode(' ', $currency['symbol']);
                        $currencySymbol = array_pop($currencySymbolInfo);

                        $message .= $rowNum . ') ' . $currency['name'] . ' ';
                        $message .= implode(' ', $currencySymbolInfo) . '*' . $currencySymbol . '* ';
                        $message .= $currency['percent_change_24h'] . '%';
                        $message .= "\n\r";
                        $rowNum++;
                    }
                }
                $keyboard = new InlineKeyboardMarkup([]);
                if ($message == '') {
                    $message = 'Ни одна криптовалюта с капитализацией свыше 500 млн. долл. не выросла за выбранный период.';
                    $bot->editMessageText(
                        $chatId,
                        $message_id,
                        $message,
                        null,
                        false,
                        $keyboard
                    );
                } else {
                    $bot->editMessageText(
                        $chatId,
                        $message_id,
                        "Топовые валюты по суточному росту с капитализацией более 500 млн. долл.: \n\r" . $message,
                        'Markdown',
                        false,
                        $keyboard
                    );
                }
            }

            if ($data == "/top_by_fall") {
                // Суточному падению
                $currencies = $crypto_currency->sortCurrenciesByChangeDayAsc('percent_change_24h');
                $message = '';

                $rowNum = 1;
                foreach ($currencies as $currency) {
                    if (floatval($currency['percent_change_24h']) < 0) {
                        $currencySymbolInfo = explode(' ', $currency['symbol']);
                        $currencySymbol = array_pop($currencySymbolInfo);

                        $message .= $rowNum . ') ' . $currency['name'] . ' ';
                        $message .= implode(' ', $currencySymbolInfo) . '*' . $currencySymbol . '* ';
                        $message .= $currency['percent_change_24h'] . '%';
                        $message .= "\n\r";
                        $rowNum++;
                    }
                }
                $keyboard = new InlineKeyboardMarkup([]);
                if ($message == '') {
                    $message = 'Ни одна криптовалюта с капитализацией свыше 500 млн. долл. не упала за выбранный период.';
                    $bot->editMessageText(
                        $chatId,
                        $message_id,
                        $message,
                        null,
                        false,
                        $keyboard
                    );
                } else {
                    $bot->editMessageText(
                        $chatId,
                        $message_id,
                        "Топовые валюты по суточному падению с капитализацией более 500 млн. долл.: \n\r" . $message,
                        'Markdown',
                        false,
                        $keyboard
                    );
                }

            }

            if ($data == "/top_by_weekly_growth") {
                // Недельному росту
                $currencies = $crypto_currency->sortCurrenciesByRaw('percent_change_7d');
                $message = '';

                $rowNum = 1;
                foreach ($currencies as $currency) {
                    if (floatval($currency['percent_change_7d']) > 0) {
                        $currencySymbolInfo = explode(' ', $currency['symbol']);
                        $currencySymbol = array_pop($currencySymbolInfo);

                        $message .= $rowNum . ') ' . $currency['name'] . ' ';
                        $message .= implode(' ', $currencySymbolInfo) . '*' . $currencySymbol . '* ';
                        $message .= $currency['percent_change_7d'] . '%';
                        $message .= "\n\r";
                        $rowNum++;
                    }
                }
                $keyboard = new InlineKeyboardMarkup([]);
                if ($message == '') {
                    $message = 'Ни одна криптовалюта с капитализацией свыше 500 млн. долл. не выросла за выбранный период.';
                    $bot->editMessageText(
                        $chatId,
                        $message_id,
                        $message,
                        null,
                        false,
                        $keyboard
                    );
                } else {
                    $bot->editMessageText(
                        $chatId,
                        $message_id,
                        "Топовые валюты по недельному росту с капитализацией более 500 млн. долл.: \n\r" . $message,
                        'Markdown',
                        false,
                        $keyboard
                    );
                }
            }

            if ($data == "/top_by_weekly_fall") {
                // Недельному падению
                $currencies = $crypto_currency->sortCurrenciesByChangeWeekAsc('percent_change_7d');
                $message = '';

                $rowNum = 1;
                foreach ($currencies as $currency) {
                    if (floatval($currency['percent_change_7d']) < 0) {
                        $currencySymbolInfo = explode(' ', $currency['symbol']);
                        $currencySymbol = array_pop($currencySymbolInfo);

                        $message .= $rowNum . ') ' . $currency['name'] . ' ';
                        $message .= implode(' ', $currencySymbolInfo) . '*' . $currencySymbol . '* ';
                        $message .= $currency['percent_change_7d'] . '%';
                        $message .= "\n\r";
                        $rowNum++;
                    }
                }
                $keyboard = new InlineKeyboardMarkup([]);
                if ($message == '') {
                    $bot->editMessageText(
                        $chatId,
                        $message_id,
                        'Ни одна криптовалюта с капитализацией свыше 500 млн. долл. не упала за выбранный период.',
                        null,
                        false,
                        $keyboard
                    );
                } else {
                    $bot->editMessageText(
                        $chatId,
                        $message_id,
                        "Топовые валюты по недельному падению с капитализацией более 500 млн. долл.: \n\r" . $message,
                        'Markdown',
                        false,
                        $keyboard
                    );
                }
            }

            if ($data == "crypto_exchange_rate_to_usd") {
                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_bitcoin', 'text' => 'Bitcoin (BTC)'],
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_ripple', 'text' => 'Ripple (XRP)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_ethereum', 'text' => 'Ethereum (ETH)'],
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_bitcoin-cash', 'text' => 'Bitcoin Cash (BCH)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_litecoin', 'text' => 'Litecoin (LTC)'],
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_monero', 'text' => 'Monero (XMR)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_dash', 'text' => 'Dash (DASH)'],
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_zcash', 'text' => 'Zcash (ZEC)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_dogecoin', 'text' => 'Dogecoin (DOGE)'],
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_stellar', 'text' => 'Stellar (STR)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_qash', 'text' => 'QASH (QASH)'],
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_iota', 'text' => 'Iota (IOTA)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_usd_from_ethereum-classic', 'text' => 'Ethereum Classic (ETC)'],
                        ],
                    ]
                );

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    "К USD: \n\r",
                    null,
                    false,
                    $keyboard
                );
            }

            if ($data == "crypto_exchange_rate_to_btc") {
                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_ripple', 'text' => 'Ripple (XRP)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_ethereum', 'text' => 'Ethereum (ETH)'],
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_bitcoin-cash', 'text' => 'Bitcoin Cash (BCH)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_litecoin', 'text' => 'Litecoin (LTC)'],
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_monero', 'text' => 'Monero (XMR)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_dash', 'text' => 'Dash (DASH)'],
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_zcash', 'text' => 'Zcash (ZEC)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_dogecoin', 'text' => 'Dogecoin (DOGE)'],
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_stellar', 'text' => 'Stellar (STR)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_qash', 'text' => 'QASH (QASH)'],
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_iota', 'text' => 'Iota (IOTA)'],
                        ],
                        [
                            ['callback_data' => 'crypto_exchange_rate_to_btc_from_ethereum-classic', 'text' => 'Ethereum Classic (ETC)'],
                        ],
                    ]
                );

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    "К USD: \n\r",
                    null,
                    false,
                    $keyboard
                );
            }

            if (preg_match('/^crypto_exchange_rate_to_usd_from/', $data)) {
                $currency = str_replace('crypto_exchange_rate_to_usd_from_', '', $data);

                $all_currencies = $database->queryToSelect(
                    "SELECT all_cryptocurrency.code, all_cryptocurrency.name, cryptocurrency_info.value_to_usd,
                    burse.name AS burse_name FROM all_cryptocurrency
                    LEFT JOIN cryptocurrency_info ON all_cryptocurrency.id = cryptocurrency_info.currency_id
                    LEFT JOIN burse ON burse.id = cryptocurrency_info.burse_id WHERE all_cryptocurrency.code='{$currency}'"
                );

                // Если все курсы = 0, то будет сообщение, что нет информации по какой-то причине
                $text = '';
                foreach ($all_currencies as $currency) {
                    if ($currency['value_to_usd'] != 0) {
                        $text .= '*' . $currency['burse_name'] . ':* ' . $currency['value_to_usd'] . " USD " . "\n\r";
                    }
                }

                if ($text !== '') {
                    $text = '1 ' . $all_currencies[0]['name'] . ':' . "\n\r" . $text;
                } else {
                    $text = 'Нет информации по этой паре валют.';
                }

                $keyboard = new InlineKeyboardMarkup( [] );

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    $text,
                    'Markdown',
                    false,
                    $keyboard
                );
            }

            if (preg_match('/^crypto_exchange_rate_to_btc_from/', $data)) {
                $currency = str_replace('crypto_exchange_rate_to_btc_from_', '', $data);

                $all_currencies = $database->queryToSelect(
                    "SELECT all_cryptocurrency.code, all_cryptocurrency.name, cryptocurrency_info.value_to_btc,
                    burse.name AS burse_name FROM all_cryptocurrency
                    LEFT JOIN cryptocurrency_info ON all_cryptocurrency.id = cryptocurrency_info.currency_id
                    LEFT JOIN burse ON burse.id = cryptocurrency_info.burse_id WHERE all_cryptocurrency.code='{$currency}'"
                );

                // Если все курсы = 0, то будет сообщение, что нет информации по какой-то причине
                $text = '';
                foreach ($all_currencies as $currency) {
                    if ($currency['value_to_btc'] != 0) {
                        $text .= '*' . $currency['burse_name'] . ':* ' . $currency['value_to_btc'] . " BTC" . "\n\r";
                    }
                }

                if ($text !== '') {
                    $text = '1 ' . $all_currencies[0]['name'] . ':' . "\n\r" . $text;
                } else {
                    $text = 'Нет информации по этой паре валют.';
                }

                $keyboard = new InlineKeyboardMarkup( [] );

                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    $text,
                    'Markdown',
                    false,
                    $keyboard
                );
            }

        }, function ($update) {
            $callback = $update->getCallbackQuery();
            if (is_null($callback) || !strlen($callback->getData()))
                return false;
            return true;
        });
    }

    public function run() {
        $this->checkBotForRegistration();
        $this->commands();
        $this->processingInlineButtons();
        $this->processingReplyButtons();
        $this->bot->run();
    }
}
