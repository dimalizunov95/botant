#!/usr/bin/php
<?php

try {

    $db_params = [
        "host" => "antpark.mysql.tools",
        "dbname" => "antpark_db",
        "user" => "antpark_db",
        "password" => "ydc66LLz",
    ];
    $database = new PDO(
        "mysql:dbname=" . $db_params['dbname'] . ";host=" . $db_params['host'].";charset=utf8mb4",
        $db_params['user'],
        $db_params['password']
    );

    $url = 'https://api.coinmarketcap.com/v1/ticker/?limit=0';
    $json = file_get_contents($url);
    $json = json_decode($json, true);


    $result = $database->query("SELECT currency_id FROM currency");

    $all_db_currencies = [];
    while ($row = $result->fetch()) {
        $all_db_currencies[] = $row['currency_id'];
    }

    foreach ($json as $currency_info) {
        foreach ($currency_info as $key => $value) {
            // на локалке не даёт вставить 'null' в БД почему-то, а на хостинге даёт
            if ($value == null) {
                $currency_info[$key] = 0;
            }
        }

        if ( !in_array($currency_info['id'], $all_db_currencies) ) {
            // there's no

            $inserted_id = 0;

            $database->query(
                'INSERT INTO currency (currency_id, name, symbol)
                  VALUES ("'.$currency_info['id'] . '", "'.$currency_info['name'] . '", "'.$currency_info['symbol'] . '")'
            );
            $inserted_id = $database->lastInsertId();

            if ($inserted_id != 0) {
                $query = 'INSERT INTO currency_info (currency_id, price_usd, price_btc, market_cap_usd, available_supply, total_supply, max_supply, last_updated)
                         VALUES ("'.$inserted_id . '", "'.$currency_info['price_usd'] . '", "'.$currency_info['price_btc'] . '", "'.$currency_info['market_cap_usd'] . '",
                         "'.$currency_info['available_supply'] . '", "'.$currency_info['total_supply'] . '", "'.$currency_info['max_supply'] . '", "'.$currency_info['last_updated'] . '")';

                $currency_info_id = $database->query($query);
                $currency_info_id = $database->lastInsertId();
            }

        } else {
            $result = $database->query("
                SELECT currency.id, currency_info.last_updated FROM currency LEFT JOIN currency_info
                ON currency.id = currency_info.currency_id
                WHERE currency.currency_id = \"{$currency_info['id']}\"
                ORDER BY currency_info.last_updated DESC LIMIT 1
            ");

            $currency = $result->fetch();
            $currency_id = $currency['id'];

            if (intval($currency['last_updated']) !== intval($currency_info['last_updated'])) {

                $query = '
                INSERT INTO currency_info (currency_id, price_usd, price_btc, market_cap_usd,
                available_supply, total_supply, max_supply, last_updated)
                VALUES ("' . $currency_id . '", "' . $currency_info['price_usd'] . '", "' . $currency_info['price_btc'] . '",
                "' . $currency_info['market_cap_usd'] . '", "' . $currency_info['available_supply'] . '", "' . $currency_info['total_supply'] . '",
                "' . $currency_info['max_supply'] . '", "' . $currency_info['last_updated'] . '")
            ';

                $database->query($query);
            }

        }
    }

    /*foreach ($json as $currency_info) {
        $result = $database->query("SELECT * FROM currency LEFT JOIN currency_info "
            . "ON currency.id = currency_info.currency_id WHERE currency.currency_id = "
            . "'{$currency_info['id']}' ORDER BY currency_info.last_updated DESC LIMIT 1");
        $database_row = $result->fetch();
        $x = 5;
        $database_row = $database_row[0];
        if (intval($currency_info['last_updated']) > intval($database_row['last_updated'])) {
            $database->query(
                "INSERT INTO currency_info (currency_id, price_usd, price_btc, market_cap_usd, available_supply, total_supply, max_supply, last_updated)
                    VALUES ('{$database_row['currency_id']}', '{$currency_info['price_usd']}', '{$currency_info['price_btc']}', '{$currency_info['market_cap_usd']}',
                    '{$currency_info['available_supply']}', '{$currency_info['total_supply']}', '{$currency_info['max_supply']}', '{$currency_info['last_updated']}')"
            );
        }
    }*/
} catch (Exception $e) {
    echo $e->getMessage();
}
