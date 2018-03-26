<?php

namespace Custom;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Botan as CoreBotan;

class Botan
{

    private $coreBotanApi;

    public function __construct($token) {
        $this->coreBotanApi = new CoreBotan($token);
    }

    public function trackWrapper(Message $message, $eventName = 'Message')
    {
        try {
            $this->coreBotanApi->track($message, $eventName);
        } catch(\TelegramBot\Api\HttpException $e) {
            Log::writeLog('botan_log.txt', 'Http exception: ' . $e->getMessage());
        } catch(\Exception $e) {
            Log::writeLog('botan_log.txt', 'Php exception: ' . $e->getMessage());
            /*Log::writeLog(
                'botan_log.txt',
                'Php exception: ' . $e->getFile() . ' line: ' . $e->getLine() . ' message: ' . $e->getMessage() . '
                    trace: ' . $e->getTraceAsString()
            );*/
        }

    }

}