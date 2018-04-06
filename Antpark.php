<?php

final class Antpark
{
    /**
     * @var Antpark
     */
    private static $instance;

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
}