<?php

namespace Custom;

/**
 * Класс Db
 * Компонент для работы с базой данных
 */
class Database
{

    public $pdo;

    function __construct() {

        /*$paramsPath = ROOT . '/config/db_params.php';
        $db_params = include($paramsPath);*/

        /*$db_params = [
            "host" => "den1.mysql1.gear.host",
            "dbname" => "botant",
            "user" => "botant",
            "password" => "Ws9caZ3~ziN!",
        ];*/
        $db_params = [
            "host" => "134.249.133.39",
            "dbname" => "botant",
            "user" => "botant",
            "password" => "IJxCJ2y1CI",
        ];

        try {
            $pdo = new \PDO(
                "mysql:dbname=" . $db_params['dbname'] . ";host=" . $db_params['host'].";charset=utf8mb4",
                $db_params['user'],
                $db_params['password']
            );
            $this->pdo = $pdo;
        } catch (\Exception $e) {
            echo "Error during creating connection to DB: " . $e->getMessage();
            die();
        }
    }

    public function queryToSelect($query) {
        $result = $this->pdo->query($query);
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        return $result->fetchAll();
    }

    public function queryToInsert($query) {
        $result = $this->pdo->query($query);
        return $this->pdo->lastInsertId();
    }

//    public function getAll

    public function parseCurrencies() {

        $url = 'https://api.coinmarketcap.com/v1/ticker/?limit=0';
        $json = file_get_contents($url);
        $json = json_decode($json, true);

        $all_db_currencies = $this->getAllValuesFromTableColumn('currency', 'currency_id');

        foreach ($json as $currency_info) {
            if ( !in_array($currency_info['id'], $all_db_currencies) ) {
                // there's no
                foreach ($currency_info as $key => $value) {
                    // на локалке не даёт вставить 'null' в БД почему-то, а на хостинге даёт
                    if ($value == null) {
                        $currency_info[$key] = 0;
                    }
                }

                $inserted_id = 0;

                $inserted_id = $this->queryToInsert(
                    'INSERT INTO currency (currency_id ,name , symbol)
                  VALUES ("'.$currency_info['id'] . '", "'.$currency_info['name'] . '", "'.$currency_info['symbol'] . '")'
                );

                if ($inserted_id != 0) {
                    $currency_info_id = $this->queryToInsert(
                        'INSERT INTO currency_info (currency_id, price_usd, price_btc, market_cap_usd, available_supply, total_supply, max_supply, last_updated)
                         VALUES ("'.$inserted_id . '", "'.$currency_info['price_usd'] . '", "'.$currency_info['price_btc'] . '", "'.$currency_info['market_cap_usd'] . '",
                         "'.$currency_info['available_supply'] . '", "'.$currency_info['total_supply'] . '", "'.$currency_info['max_supply'] . '", "'.$currency_info['last_updated'] . '")'
                    );
                }

            }
        }

        foreach ($json as $currency_info) {
            $database_row = $this->queryToSelect("SELECT * FROM currency LEFT JOIN currency_info "
                . "ON currency.id = currency_info.currency_id WHERE currency.currency_id = "
                . "'{$currency_info['id']}' ORDER BY currency_info.last_updated DESC LIMIT 1");
            $database_row = $database_row[0];
            if (intval($currency_info['last_updated']) > intval($database_row['last_updated'])) {
                $this->queryToInsert(
                    "INSERT INTO currency_info (currency_id, price_usd, price_btc, market_cap_usd, available_supply, total_supply, max_supply, last_updated)
                    VALUES ('{$database_row['currency_id']}', '{$currency_info['price_usd']}', '{$currency_info['price_btc']}', '{$currency_info['market_cap_usd']}',
                    '{$currency_info['available_supply']}', '{$currency_info['total_supply']}', '{$currency_info['max_supply']}', '{$currency_info['last_updated']}')"
                );
            }
        }

    }

    public function getAllValuesFromTableColumn($table_name, $column_name) {
        $result = $this->pdo->query("SELECT $column_name FROM $table_name");

        $to_return = [];
        while ($row = $result->fetch()) {
            $to_return[] = $row[$column_name];
        }

        return $to_return;

    }

    public function registerUser($id, $firstName, $lastName) {
        $is_user_registered = $this->queryToSelect("
            SELECT COUNT(*) FROM all_users WHERE telegram_user_id = '{$id}'
        ");
        $is_user_registered = $is_user_registered[0]['COUNT(*)'];

        if (!$is_user_registered) {
            $inserted_id = $this->queryToInsert("
                INSERT INTO all_users (telegram_user_id, first_name, last_name)
                VALUES ('{$id}', '{$firstName}', '{$lastName}')
            ");
            return $inserted_id;
        } else {
            $id = $this->queryToSelect("
                SELECT id FROM all_users WHERE telegram_user_id = '{$id}'
            ");
            return $id[0]['id'];
        }
    }

    public function registerChat($chat_id, $chat_type = null) {
        $is_chat_registered = $this->queryToSelect("
            SELECT COUNT(*) FROM all_chats WHERE chat_id = '{$chat_id}'
        ");
        $is_chat_registered = $is_chat_registered[0]['COUNT(*)'];
        $current_time = time();

        if (!$is_chat_registered) {
            $inserted_id = $this->queryToInsert("
                INSERT INTO all_chats (chat_id, last_action_time, chat_type)
                VALUES ('{$chat_id}', '{$current_time}', '{$chat_type}')
            ");
            return $inserted_id;
        } else {
            $this->queryToInsert("
                UPDATE all_chats SET last_action_time = '{$current_time}' WHERE chat_id = '{$chat_id}'
            ");
            $id = $this->queryToSelect("
                SELECT id FROM all_chats WHERE chat_id = '{$chat_id}'
            ");
            return $id[0]['id'];
        }
    }

    public function isAdmin($user_db_id) {
        $admin = $this->queryToSelect(
            "SELECT COUNT(*) AS count FROM admin_users WHERE user_id = '{$user_db_id}'"
        );
        if (intval($admin[0]['count']) > 0) {
            return true;
        }
        return false;
    }
}

