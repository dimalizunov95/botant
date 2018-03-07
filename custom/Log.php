<?php

namespace Custom;

use Custom\Database;

/**
 * Class BotApi
 *
 * @package TelegramBot\Api
 */
class Log
{

    /*
     * Класс для написания логов
     *
     * При объявлении класса нужно указать название файла в папке logs
     * и дать ему права 777
     */

    protected $log_file;

    protected $path_to_log;

        function __construct($log_file) {
        $this->path_to_log = ROOT . '/logs/' . $log_file;
    }

    public function startLog() {
        $file_content = file_get_contents($this->path_to_log);
        $file_content .= "\n\r" . '-----LOG START-----' . "\n\r";
        $this->log_file = $file_content;
    }

    public function endLog() {
        $this->log_file .= "\n\r" . '-----LOG END-----' . "\n\r";
        file_put_contents($this->path_to_log, $this->log_file);
    }

    public function addLine($line_of_text) {
        $this->log_file .= "\n\r" . $line_of_text;
    }
}