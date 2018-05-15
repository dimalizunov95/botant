<?php

namespace Custom;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Exception;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

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
     * @var BotApi
     */
    protected $bot;

    protected $database;

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

        $bot->command('start', function ($message) use ($bot, $database) {
            /** @var \TelegramBot\Api\Types\Message $message */
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

        $bot->command('currency', function ($message) use ($bot, $database) {
            /** @var \TelegramBot\Api\Types\Message $message */
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

        $bot->command('getchatid', function ($message) use ($bot, $database) {

            /** @var \TelegramBot\Api\Types\Message $message */
            $cid = $message->getChat()->getId();
            $user_telegram_id = $message->getFrom()->getId();
            $user = new User(
                $user_telegram_id,
                $message->getFrom()->getFirstName(),
                $message->getFrom()->getLastName()
            );

            if ($user->isAdmin() || 'private' === $message->getChat()->getType()) {
                $bot->sendMessage($cid, "Chat ID: " . $cid);
            }
        });

        // помощ
        $bot->command('help', function ($message) use ($bot, $database) {
            /** @var \TelegramBot\Api\Types\Message $message */
            $answer = 'Команды:
        /help - помощь';
            $bot->sendMessage($message->getChat()->getId(), $answer);
        });

        $bot->command('sendnewbuttons', function ($message) use ($bot, $database) {
            /** @var \TelegramBot\Api\Types\Message $message */
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
            /** @var \TelegramBot\Api\Types\Message $message */
            $cid = $message->getChat()->getId();
            $keyboard = new ReplyKeyboardMarkup(
                \Antpark::getInstance()->getMainKeyboard(),
                false, true
            );

            $bot->sendMessage($cid, 'Вот твоя клавиатура: ', false, null, null, $keyboard);
        });

        $bot->command("test", function ($message) use ($bot, $database) {
            /** @var \TelegramBot\Api\Types\Message $message */

            $user = new User(
                $message->getFrom()->getId(),
                $message->getFrom()->getFirstName(),
                $message->getFrom()->getLastName()
            );

            if ($user->isAdmin()) {

                $message = $message->toJson();
                $bot->sendMessage($message->getChat()->getId(), $message);
            }
        });

        $bot->command("ibutton", function ($message) use ($bot, $database) {
            /** @var \TelegramBot\Api\Types\Message $message */
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

        $bot->command("boobs", function ($message) use ($bot, $database) {

            $from = $message->getFrom()->getId();

            $user = $database->queryToSelect("
                SELECT * FROM all_users WHERE telegram_user_id = '{$from}'
            ");
            $user_id = $user[0]['id'];

            $q = $database->queryToSelect("
                SELECT COUNT(*) FROM boobs_calls_statistics
                WHERE user_id = '{$user_id}'
            ");

            $q = $q[0]['COUNT(*)'];
            $text = $user[0]['first_name'] . ": " . $q;

            $bot->sendMessage($message->getChat()->getId(), $text);
        });

        $bot->command("boobs_stat", function ($message) use ($bot, $database) {
            $cid = $database->registerChat(
                $message->getChat()->getId(),
                $message->getChat()->getType()
            );
            $from = $message->getFrom()->getId();

            $user = $database->queryToSelect("
                SELECT * FROM all_users WHERE telegram_user_id = '{$from}'
            ");
            $user_id = $user[0]['id'];

            $conflictLikes = $database->queryToSelect("
                SELECT ((COUNT(*) - SUM(liked_or_not)) / SUM(liked_or_not)) as rate
                    FROM  boobs_likes
                    WHERE chat_id = '$cid'
                    GROUP BY boobs_image_id
                    HAVING (SUM(liked_or_not) / COUNT(*)) < 0.5
                        AND SUM(liked_or_not) >= 1
                        AND FIND_IN_SET($user_id, GROUP_CONCAT(user_id))
                        AND SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(liked_or_not), ',', FIND_IN_SET($user_id, GROUP_CONCAT(user_id))), ',', -1) = 1
            ");
            $conflictLikesRate = 0;
            foreach ($conflictLikes as $conflictLike) {
                $conflictLikesRate += (float) $conflictLike['rate'];
            }

            $conflictDisLikes = $database->queryToSelect("
                SELECT (SUM(liked_or_not) / (COUNT(*) - SUM(liked_or_not))) as rate
                    FROM  boobs_likes
                    WHERE chat_id = '$cid'
                    GROUP BY boobs_image_id
                    HAVING (SUM(liked_or_not) / COUNT(*)) > 0.5
                        AND (COUNT(*) - SUM(liked_or_not)) >= 1
                        AND FIND_IN_SET($user_id, GROUP_CONCAT(user_id))
                        AND SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(liked_or_not), ',', FIND_IN_SET($user_id, GROUP_CONCAT(user_id))), ',', -1) = 0
            ");
            $conflictDisLikesRate = 0;
            foreach ($conflictDisLikes as $conflictDisLike) {
                $conflictDisLikesRate += (float) $conflictDisLike['rate'];
            }

            $text = $user[0]['first_name'] . ":
            Лайк против большинства (баллов): " . (ceil($conflictLikesRate * 100) / 100) . "
            Дизлайк против большинства (баллов): " . (ceil($conflictDisLikesRate * 100) / 100);

            $bot->sendMessage($message->getChat()->getId(), $text);
        });

        $bot->command("pushupsstat", function ($message) use ($bot, $database) {
            $currMonthStart = strtotime( 'first day of ' . date( 'F Y'));

            $totalPushupsNum = $database->queryToSelect("
                SELECT COUNT(*) FROM pushups_event WHERE time>{$currMonthStart}
            ");
            $totalPushupsPartByUser = $database->queryToSelect("
                SELECT 
                    all_users.first_name, 
                    all_users.last_name, 
                    COUNT(*) as pushups_done,
                    (COUNT(*) / (SELECT COUNT(*) FROM pushups_event WHERE time>{$currMonthStart})) * 100 AS 'percentage_to_all_pushups'
                FROM pushups_done 
                LEFT JOIN all_users ON all_users.id=pushups_done.user_id
                LEFT JOIN pushups_event ON pushups_event.id=pushups_done.pushup_event
                WHERE pushups_event.time>{$currMonthStart}
                GROUP BY user_id
                ORDER BY percentage_to_all_pushups DESC
            ");

            $sendMessage = 'Статистика за ' . \Antpark::getInstance()->getMonthName() . "\n\r\n\r";
            $sendMessage .= "Всего было {$totalPushupsNum[0]['COUNT(*)']} отжимашек.\n\r\n\r";
            $sendMessage .= "Посещаемость отжимашек:\n\r";
            foreach ($totalPushupsPartByUser as $userPushups) {
                $sendMessage .= $userPushups['first_name'] . ' ';
                $sendMessage .= $userPushups['last_name'] . ': ';
                $sendMessage .= (int)$userPushups['pushups_done'] . ' (';
                $sendMessage .= (int)$userPushups['percentage_to_all_pushups'] . "%)\n\r";
            }

            $bot->sendMessage($message->getChat()->getId(), $sendMessage);
        });

        $bot->command("boobs", function ($message) use ($bot, $database) {

            $from = $message->getFrom()->getId();

            $user = $database->queryToSelect("
                SELECT * FROM all_users WHERE telegram_user_id = '{$from}'
            ");
            $user_id = $user[0]['id'];

            $q = $database->queryToSelect("
                SELECT COUNT(*) FROM boobs_calls_statistics
                WHERE user_id = '{$user_id}'
            ");

            $q = $q[0]['COUNT(*)'];
            $text = $user[0]['first_name'] . ": " . $q;

            $bot->sendMessage($message->getChat()->getId(), $text);
        });

        $bot->command("boobs_stat", function ($message) use ($bot, $database) {
            $cid = $database->registerChat(
                $message->getChat()->getId(),
                $message->getChat()->getType()
            );
            $from = $message->getFrom()->getId();

            $user = $database->queryToSelect("
                SELECT * FROM all_users WHERE telegram_user_id = '{$from}'
            ");
            $user_id = $user[0]['id'];

            $conflictLikes = $database->queryToSelect("
                SELECT ((COUNT(*) - SUM(liked_or_not)) / SUM(liked_or_not)) as rate
                    FROM  boobs_likes
                    WHERE chat_id = '$cid'
                    GROUP BY boobs_image_id
                    HAVING (SUM(liked_or_not) / COUNT(*)) < 0.5
                        AND SUM(liked_or_not) >= 1
                        AND FIND_IN_SET($user_id, GROUP_CONCAT(user_id))
                        AND SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(liked_or_not), ',', FIND_IN_SET($user_id, GROUP_CONCAT(user_id))), ',', -1) = 1
            ");
            $conflictLikesRate = 0;
            foreach ($conflictLikes as $conflictLike) {
                $conflictLikesRate += (float) $conflictLike['rate'];
            }

            $conflictDisLikes = $database->queryToSelect("
                SELECT (SUM(liked_or_not) / (COUNT(*) - SUM(liked_or_not))) as rate
                    FROM  boobs_likes
                    WHERE chat_id = '$cid'
                    GROUP BY boobs_image_id
                    HAVING (SUM(liked_or_not) / COUNT(*)) > 0.5
                        AND (COUNT(*) - SUM(liked_or_not)) >= 1
                        AND FIND_IN_SET($user_id, GROUP_CONCAT(user_id))
                        AND SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(liked_or_not), ',', FIND_IN_SET($user_id, GROUP_CONCAT(user_id))), ',', -1) = 0
            ");
            $conflictDisLikesRate = 0;
            foreach ($conflictDisLikes as $conflictDisLike) {
                $conflictDisLikesRate += (float) $conflictDisLike['rate'];
            }

            $text = $user[0]['first_name'] . ":
            Лайк против большинства (баллов): " . (ceil($conflictLikesRate * 100) / 100) . "
            Дизлайк против большинства (баллов): " . (ceil($conflictDisLikesRate * 100) / 100);

            $bot->sendMessage($message->getChat()->getId(), $text);
        });

        $bot->command("pushupsstatall", function ($message) use ($bot, $database) {
            $currMonthStart = strtotime( 'first day of ' . date( 'F Y'));

            $totalPushupsNum = $database->queryToSelect("
                SELECT COUNT(*) FROM pushups_event
            ");
            $totalPushupsPartByUser = $database->queryToSelect("
                SELECT 
                    all_users.first_name, 
                    all_users.last_name, 
                    COUNT(*) as pushups_done,
                    (COUNT(*) / (SELECT COUNT(*) FROM pushups_event)) * 100 AS 'percentage_to_all_pushups'
                FROM pushups_done 
                LEFT JOIN all_users ON all_users.id=pushups_done.user_id
                GROUP BY user_id
                ORDER BY percentage_to_all_pushups DESC
            ");

            $sendMessage = "Статистика за всё время.\n\r\n\r";
            $sendMessage .= "Всего было {$totalPushupsNum[0]['COUNT(*)']} отжимашек.\n\r\n\r";
            $sendMessage .= "Посещаемость отжимашек:\n\r";
            foreach ($totalPushupsPartByUser as $userPushups) {
                $sendMessage .= $userPushups['first_name'] . ' ';
                $sendMessage .= $userPushups['last_name'] . ': ';
                $sendMessage .= (int)$userPushups['pushups_done'] . ' (';
                $sendMessage .= (int)$userPushups['percentage_to_all_pushups'] . "%)\n\r";
            }

            $bot->sendMessage($message->getChat()->getId(), $sendMessage);
        });



    }

    protected function processingReplyButtons() {
        $bot = $this->bot;
        $database = $this->database;
        $botanApi = $this->botanApi;

        $bot->on(function ($Update) use ($bot, $database, $botanApi) {
            /** @var \TelegramBot\Api\Types\Update $Update */
            /** @var \TelegramBot\Api\Types\Message $message */
            $message = $Update->getMessage();

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

            if (strpos($mtext, "Сиськи") !== false) {

                $parsing = new Parse();
                $boobs_arr = $parsing->getBoobs();

                if (isset($boobs_arr['error'])) {
                    $bot->sendMessage($cid, 'Там плохая картинка, попробуйте ещё');
                    return;
                }

                $bot->sendChatAction(
                    $cid,
                    'upload_photo'
                );

                $boobs_id = $boobs_arr['boobs_id'];
                $likes_to_image = $boobs_arr['likes_to_image'];
                $dislikes_to_image = $boobs_arr['dislikes_to_image'];
                $pic = $boobs_arr['pic_link'];

                $database->queryToInsert("
                    INSERT INTO boobs_calls_statistics (user_id, chat_id, insert_time)
                    VALUES ('{$user->user_db_id}', '{$chat_db_id}', '{$current_time}')
                ");


                $callback_like = '/like_' . $boobs_id;
                $callback_dislike = '/dislike_' . $boobs_id;

                $text_likes = hex2bin('F09F918D') . ' ' . $likes_to_image;
                $text_dislikes = hex2bin('F09F918E') . ' ' . $dislikes_to_image;

                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => $callback_like, 'text' => $text_likes],
                            ['callback_data' => $callback_dislike, 'text' => $text_dislikes]
                        ]
                    ]
                );

                $bot->sendPhoto($cid, $pic, false, null, $keyboard);

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

            } else {
                //Обработка тупо всех остальных сообщений в чате
            }

        }, function ($message) {
            return true; // когда тут true - команда проходит
        });

    }

    protected function processingInlineButtons() {
        /** @var BotApi $bot */
        $bot = $this->bot;
        $database = $this->database;

        $bot->on(function ($update) use ($bot, $database) {
            /** @var \TelegramBot\Api\Types\Update $update */
            $callback = $update->getCallbackQuery();
            /** @var \TelegramBot\Api\Types\Message $message */
            $message = $callback->getMessage();
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

            if (preg_match('/^\/like/', $data)) {

                $img_id = str_replace('/like_', '', $data);
                $message_id = $message->getMessageId();

                $x = $database->queryToSelect("
                    SELECT COUNT(*) FROM boobs_likes WHERE boobs_image_id = '{$img_id}'
                    AND user_id = '{$user->user_db_id}'
                ");

                if (intval($x[0]['COUNT(*)'])) {
                    $database->queryToInsert("
                        UPDATE boobs_likes SET liked_or_not= 1 WHERE user_id = '{$user->user_db_id}' AND boobs_image_id = '{$img_id}'
                    ");
                } else {
                    $database->queryToInsert("
                        INSERT INTO boobs_likes (boobs_image_id, user_id, liked_or_not, chat_id, insert_time)
                        VALUES ('{$img_id}', '{$user->user_db_id}', '1', '{$chat_db_id}', '{$current_time}')
                    ");
                }

                $votes_to_image = $this->database->queryToSelect("
                    SELECT
                        COUNT(CASE WHEN liked_or_not = 1 AND boobs_image_id = '{$img_id}' THEN 1 END) AS likes,
                        COUNT(CASE WHEN liked_or_not = 0 AND boobs_image_id = '{$img_id}' THEN 1 END) AS dislikes
                    FROM boobs_likes
                ");
                $likes_to_image = $votes_to_image[0]['likes'];
                $dislikes_to_image = $votes_to_image[0]['dislikes'];

                $callback_like = '/like_' . $img_id;
                $callback_dislike = '/dislike_' . $img_id;

                if ( $message->getChat()->getType() == "group" ) {
                    if ($likes_to_image == 1) {
                        $user_data = $database->queryToSelect(
                            "SELECT all_users.first_name, all_users.last_name FROM all_users
                         LEFT JOIN boobs_likes ON all_users.id=boobs_likes.user_id
                         WHERE boobs_likes.liked_or_not = 1 AND boobs_likes.boobs_image_id = {$img_id}"
                        );
                        $user_data = $user_data[0];
                        $likes_to_image .= " [{$user_data['first_name']} {$user_data['last_name']}]";
                    }
                    if ($dislikes_to_image == 1) {
                        $user_data = $database->queryToSelect(
                            "SELECT all_users.first_name, all_users.last_name FROM all_users
                         LEFT JOIN boobs_likes ON all_users.id=boobs_likes.user_id
                         WHERE boobs_likes.liked_or_not = 0 AND boobs_likes.boobs_image_id = {$img_id}"
                        );
                        $user_data = $user_data[0];
                        $dislikes_to_image .= " [{$user_data['first_name']} {$user_data['last_name']}]";
                    }
                }

                $text_likes = hex2bin('F09F918D')  . ' ' . $likes_to_image;
                $text_dislikes = hex2bin('F09F918E') . ' ' . $dislikes_to_image;

                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => $callback_like, 'text' => $text_likes],
                            ['callback_data' => $callback_dislike, 'text' => $text_dislikes]
                        ]
                    ]
                );

                $bot->answerCallbackQuery($callback->getId());

                $bot->editMessageReplyMarkup(
                    $chatId,
                    $message_id,
                    $keyboard
                );

            }

            if (preg_match('/^\/dislike/', $data)) {
                $img_id = str_replace('/dislike_', '', $data);
                $message_id = $message->getMessageId();

                $x = $database->queryToSelect("
                    SELECT COUNT(*) FROM boobs_likes WHERE boobs_image_id = '{$img_id}'
                    AND user_id = '{$user->user_db_id}'
                ");

                if (intval($x[0]['COUNT(*)'])) {
                    $database->queryToInsert("
                        UPDATE boobs_likes SET liked_or_not= 0 WHERE user_id = '{$user->user_db_id}' AND boobs_image_id = '{$img_id}'
                    ");
                } else {
                    $database->queryToInsert("
                        INSERT INTO boobs_likes (boobs_image_id, user_id, liked_or_not, chat_id, insert_time)
                        VALUES ('{$img_id}', '{$user->user_db_id}', '0', '{$chat_db_id}', '{$current_time}')
                    ");
                }

                $votes_to_image = $this->database->queryToSelect("
                    SELECT
                        COUNT(CASE WHEN liked_or_not = 1 AND boobs_image_id = '{$img_id}' THEN 1 END) AS likes,
                        COUNT(CASE WHEN liked_or_not = 0 AND boobs_image_id = '{$img_id}' THEN 1 END) AS dislikes
                    FROM boobs_likes
                ");
                $likes_to_image = $votes_to_image[0]['likes'];
                $dislikes_to_image = $votes_to_image[0]['dislikes'];

                $callback_like = '/like_' . $img_id;
                $callback_dislike = '/dislike_' . $img_id;

                if ( $message->getChat()->getType() == "group" ) {
                    if ($likes_to_image == 1) {
                        $user_data = $database->queryToSelect(
                            "SELECT all_users.first_name, all_users.last_name FROM all_users
                         LEFT JOIN boobs_likes ON all_users.id=boobs_likes.user_id
                         WHERE boobs_likes.liked_or_not = 1 AND boobs_likes.boobs_image_id = {$img_id}"
                        );
                        $user_data = $user_data[0];
                        $likes_to_image .= " [{$user_data['first_name']} {$user_data['last_name']}]";
                    }
                    if ($dislikes_to_image == 1) {
                        $user_data = $database->queryToSelect(
                            "SELECT all_users.first_name, all_users.last_name FROM all_users
                         LEFT JOIN boobs_likes ON all_users.id=boobs_likes.user_id
                         WHERE boobs_likes.liked_or_not = 0 AND boobs_likes.boobs_image_id = {$img_id}"
                        );
                        $user_data = $user_data[0];
                        $dislikes_to_image .= " [{$user_data['first_name']} {$user_data['last_name']}]";
                    }
                }

                $text_likes = hex2bin('F09F918D')  . ' ' . $likes_to_image;
                $text_dislikes = hex2bin('F09F918E') . ' ' . $dislikes_to_image;

                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['callback_data' => $callback_like, 'text' => $text_likes],
                            ['callback_data' => $callback_dislike, 'text' => $text_dislikes]
                        ]
                    ]
                );

                $bot->answerCallbackQuery($callback->getId());

                $bot->editMessageReplyMarkup(
                    $chatId,
                    $message_id,
                    $keyboard
                );
            }

            if (false !== strpos($data, "pushups_done")) {
                $lastPushupsEvent = $database->queryToSelect(
                    "SELECT * FROM pushups_event ORDER BY time DESC LIMIT 1"
                );
                $lastPushupsEvent = $lastPushupsEvent[0];


                $newMessage =  "Пора отжиматься!\n\r\n\rОтжались:\n\r";

                // Get pushup event id
                $pushupsEvent = $database->queryToSelect(
                    "SELECT * FROM pushups_event WHERE message_id = '" . $message_id . "'"
                );
                $pushupsEvent = $pushupsEvent[0];

                // If this is not the very last pushups event, you're not allowed to pushup
                if ($lastPushupsEvent['time'] !== $pushupsEvent['time']) {
                    $bot->answerCallbackQuery($callback->getId());
                    return;
                }

                // Inserting that pushup was made by this user
                $userInsertPushupsId = $database->queryToInsert(
                    "INSERT IGNORE INTO pushups_done (user_id, pushup_event) 
                          VALUES ('{$user->user_db_id}', '{$pushupsEvent['id']}')"
                );

                if (intval($userInsertPushupsId) === 0) {
                    $bot->answerCallbackQuery($callback->getId());
                    return;
                }

                // Names of people who made pushups
                $pushupsDone = $database->queryToSelect(
                    "SELECT all_users.first_name, all_users.last_name FROM pushups_done 
                          LEFT JOIN all_users ON all_users.id = pushups_done.user_id
                          WHERE pushup_event = '" . $pushupsEvent['id'] . "'"
                );

                foreach ($pushupsDone as $pushupUser) {
                    $newMessage .= $pushupUser['first_name'] . ' ' . $pushupUser['last_name'] . "\n\r";
                }

                $keyboard = $this->createInlineKeyboard([[
                        ['callback_data' => 'pushups_done', 'text' => 'Я отжался!'],
                ]]);

                //todo Нужно ли ещё раз отправлять клвиатуру
                $bot->editMessageText(
                    $chatId,
                    $message_id,
                    $newMessage,
                    null,
                    false,
                    $keyboard
                );
//                $bot->sendMessage($chatId, json_encode($pushupsDone));

                $bot->answerCallbackQuery($callback->getId());
            }


        }, function ($update) {
            /** @var \TelegramBot\Api\Types\Update $update */
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

    public function sendMsg(
        $chatId,
        $text,
        $parseMode = false,
        $disablePreview = null,
        $replyToMessageId = null,
        $keyboard = null
    ) {
        if (!is_null($keyboard)) {
            $result = $this->bot->sendMessage($chatId, $text, $parseMode, $disablePreview, $replyToMessageId, $keyboard);
        } else {
            $result = $this->bot->sendMessage($chatId, $text);
        }
        return $result;
    }

    public function createKeyboard($keyboardMarkup) {
        $keyboard = new ReplyKeyboardMarkup(
            $keyboardMarkup,
            false, true
        );
        return $keyboard;
    }

    public function createInlineKeyboard($keyboardMarkup) {
        $keyboard = new InlineKeyboardMarkup($keyboardMarkup);
        return $keyboard;
    }

}
