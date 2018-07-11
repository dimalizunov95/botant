<?php

final class Antpark
{
    /**
     * @var Antpark
     */
    private static $instance;

    public static $timeDiff = 3;

    private $dbConnection;

    private $mainKeyboard = [
        [
            ["text" => "Курс криптовалют"],
            ["text" => "Калькулятор криптовалют"],
        ],
        [
            ["text" => "Курс валют"],
            ["text" => "Арбитраж"],
        ],
        [
            ["text" => "Топовые криптовалюты"]
        ]
    ];

    private $botToken = "529920509:AAEteMXxoTlkMvgbWYqkwFAlC9hfcShCvHM";

    private $creatorChatId = 399527521;

    private $testCryptoChatId = -303854533;

    private $months = [
        1 => 'январь',
        2 => 'февраль',
        3 => 'март',
        4 => 'апрель',
        5 => 'май',
        6 => 'июнь',
        7 => 'июль',
        8 => 'август',
        9 => 'сентябрь',
        10 => 'октябрь',
        11 => 'ноябрь',
        12 => 'декабрь',
    ];

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): Antpark
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        $this->dbConnection = new \Custom\Database();
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    private function __wakeup()
    {
    }

    public function Db() {
        return $this->dbConnection;
    }

    /*public function getMainKeyboard() {
        return $this->mainKeyboard;
    }*/

    public function getMainKeyboard() {
        return [
            [
                ["text" => "Сиськи"],
                ["text" => "Курс биткоина"],
            ],
        ];
    }

    public function getMainKeyboardForPrivateChat() {
        return [
            [
                ["text" => "Сиськи"],
                ["text" => "Курс биткоина"],
            ],
            [
                ["text" => "Планировщик задач"],
            ],
        ];
    }

    public function getMainKeyboardTest() {
        return [
            [
                ["text" => "Курс криптовалют " . hex2bin('F09F93B2')],
                ["text" => "Калькулятор криптовалют " . hex2bin('F09F939F')],
            ],
            [
                ["text" => "Курс валют " . hex2bin('F09F92B5')],
                ["text" => "Арбитраж " . hex2bin('F09F938A')],
            ],
            [
                ["text" => "Топовые криптовалюты " . hex2bin('F09F9388')]
            ]
        ];
    }

    public function getToken() {
        return $this->botToken;
    }

    public function getCreatorChatId() {
        return $this->creatorChatId;
    }

    public function getTestCryptoChatId() {
        return $this->testCryptoChatId;
    }

    public function getMonthName($monthNum = null) {
        if (is_null($monthNum)) {
            return $this->months[date('n')];
        }
        return $this->months[$monthNum];
    }

    public function getAllPushupsTime()
    {
        return [
            "10:00",
            "11:30",
            "15:00",
            "16:30",
//            "17:00",
        ];
    }

}