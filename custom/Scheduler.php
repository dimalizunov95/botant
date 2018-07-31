<?php

namespace Custom;

use Custom\Bot;

class Scheduler
{
    /**
     * @var Database
     */
    private $db;

    /**
     * Scheduler constructor.
     */
    public function __construct() {
        $this->db = \Antpark::getInstance()->Db();
    }

    /**
     * @return array
     */
    private function _getAllTasksToSend()
    {

        $allTasks = $this->db->queryToSelect(
            "SELECT scheduler.*, all_chats.chat_id as chat_telegram_id FROM scheduler
                  LEFT JOIN all_chats ON all_chats.id = scheduler.chat_id 
                  WHERE scheduler.message_text IS NOT NULL"
        );

        $messagesToSend = [];

        foreach ($allTasks as $task) {
            list($taskHourSendFrom, $taskMinuteSendFrom) = explode(':', $task['send_from']);
            list($taskHourSendTo, $taskMinuteSendTo) = explode(':', $task['send_to']);

            $taskMinutesSendFrom = $taskHourSendFrom * 60 + $taskMinuteSendFrom;
            $taskMinutesSendTo = $taskHourSendTo * 60 + $taskMinuteSendTo;

            if ($this->_isShouldSendMessage($taskMinutesSendFrom, $taskMinutesSendTo, $task['periodicity'])) {
                $messagesToSend[] = [
                    'message' => $task['message_text'],
                    'chat_id' => $task['chat_telegram_id'],
                ];
            }

        }
        return $messagesToSend;
    }

    /**
     * @param $taskMinutesSendFrom
     * @param $taskMinutesSendTo
     * @param $taskPeriodicity
     * @return bool
     */
    private function _isShouldSendMessage($taskMinutesSendFrom, $taskMinutesSendTo, $taskPeriodicity)
    {
        $currMinutes = $this->_getCurrentTimeInMinutes();

        // is current time in range from and to
        if (!($currMinutes >= $taskMinutesSendFrom && $currMinutes <= $taskMinutesSendTo)) {
            return false;
        }

        while ($taskMinutesSendFrom < $taskMinutesSendTo) {
            if (($taskMinutesSendFrom + 2) >= $currMinutes && ($taskMinutesSendFrom - 2) <= $currMinutes) {
                return true;
            }
            $taskMinutesSendFrom += $taskPeriodicity;
        }

        return false;
    }

    private function _getCurrentTimeInMinutes()
    {
        list($currHour, $currMinute, $weekDay) = explode(':', date('G:i:w'));
        $currHour += \Antpark::$timeDiff;
        return $currHour * 60 + $currMinute;
    }

    private function _pushupsMessage()
    {
        if (date('w') == 6 || date('w') == 0) {
            return false;
        }

        $currentTime = $this->_getCurrentTimeInMinutes();

        foreach (\Antpark::getInstance()->getAllPushupsTime() as $time) {

            list($pushHour, $pushMinute) = explode(':', $time);
            $pushUpTime = $pushHour * 60 + $pushMinute;

            if (($pushUpTime + 2) >= $currentTime && ($pushUpTime - 2) <= $currentTime) {
                $this->sendPushupsMessage();
                return true;
            }
        }
    }

    /**
     * @return array
     */
    private function _getAllMessagesToSend()
    {
        $allMessagesToSend = [];
        $allMessagesToSend += $this->_getAllTasksToSend();
        return $allMessagesToSend;
    }

    /**
     * @return bool
     */
    public function sendMessagesToChats()
    {
        $this->_pushupsMessage();

        $getAllMessagesToSend = $this->_getAllMessagesToSend();

        if (empty($getAllMessagesToSend)) {
            return false;
        }

        $bot = new Bot(\Antpark::getInstance()->getToken());

        foreach ($getAllMessagesToSend as $message) {
            $bot->sendMsg(
                $message['chat_id'],
                $message['message']
            );
        }
        return true;
    }

    public function sendPushupsMessage()
    {
        $bot = new \Custom\Bot(\Antpark::getInstance()->getToken());

        $keyboard = [
            [
                ['callback_data' => 'pushups_done', 'text' => 'Я размялся!'],
            ]
        ];
        $keyboard = $bot->createInlineKeyboard($keyboard);

        $result = $bot->sendMsg(
            \Antpark::getInstance()->getTestCryptoChatId(),
            'Пора разминаться!',
            $keyboard
        );
        $currentTime = time();

        $database = \Antpark::getInstance()->Db();
        $database->queryToInsert(
            "INSERT INTO pushups_event (message_id, time) VALUES ('{$result->getMessageId()}', '{$currentTime}')"
        );
    }

}