<?php

namespace Custom;

use \Custom\Database;

/**
 * Class BotApi
 *
 * @package TelegramBot\Api
 */
class Burse
{

    protected $database;

    function __construct() {
        $this->database = \Antpark::getInstance()->Db();
    }

    /*
     * Returns active burses in database
     */
    public function getAllActiveBurses() {
        $burses = $this->database->queryToSelect(
            "SELECT id, code, name FROM burse WHERE turned_on = 1"
        );
        $result = [];
        foreach ($burses as $burse) {
            $result[$burse['code']]['id'] = $burse['id'];
            $result[$burse['code']]['name'] = $burse['name'];
        }
        return $result;
    }
}