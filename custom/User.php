<?php

namespace Custom;

/**
 * Class BotApi
 *
 * @package TelegramBot\Api
 */
class User
{

    /*
     * Класс для написания логов
     *
     * При объявлении класса нужно указать название файла в папке logs
     * и дать ему права 777
     */

//    protected $database;

    public $current_time;

    public $user_db_id;

    function __construct($telegram_id, $telegram_name, $telegram_last_name = NULL) {
//        $this->database = new Database();
        $this->current_time = time();
        $this->user_db_id = \Antpark::getInstance()->Db()->queryToInsert(
            "INSERT INTO all_users (telegram_user_id, first_name, last_name, last_action_time)
            VALUES ('{$telegram_id}', '{$telegram_name}', '{$telegram_last_name}', {$this->current_time})
            ON DUPLICATE KEY UPDATE last_action_time = VALUES(last_action_time);"
        );
    }

    public function isAdmin() {
        $admin = \Antpark::getInstance()->Db()->queryToSelect(
            "SELECT COUNT(*) AS count FROM admin_users WHERE user_id = '{$this->user_db_id}'"
        );
        if (intval($admin[0]['count']) > 0) {
            return true;
        }
        return false;
    }
}