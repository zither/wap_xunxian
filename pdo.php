<?php

if (!class_exists('DB')) {
    class DB
    {
        private static $pdo;

        public static function pdo()
        {
            self::connect();
            return self::$pdo;
        }

        public static function conn()
        {
            global $db;
            return $db;
        }

        private static function connect()
        {
            global $configs;
            if (!self::$pdo) {
                $dsn = "mysql:host={$configs['db_host']};dbname={$configs['db_name']}";
                self::$pdo = new PDO($dsn, $configs['db_user'], $configs['db_password']);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->query("SET NAMES utf8mb4");
            }
        }
    }
}


$dblj = DB::pdo();