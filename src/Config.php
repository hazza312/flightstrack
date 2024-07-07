<?php

class Config
{
    private static PDO $db;
    private static Config $instance;

    private function __construct()
    {
        self::$db = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}", $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    }

    public function getUserId(): int
    {
        return $_SESSION['flightsid'];
    }

    public function getUserName(): string
    {
        return $_SESSION['flightsname'];
    }

    public static function get(): Config
    {
        if (!isset(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }
}