<?php namespace db;

class Config
{
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = '3306';
    const DEFAULT_USER = 'root';
    const DEFAULT_PASS = '';
    const DEFAULT_CHARSET = 'UTF8';
    const DEFAULT_DB_NAME = 'shop_db';

    public static function getConnectionString($database, $driver = 'mysql'): string
    {
        return "$driver:host=".self::DEFAULT_HOST.";port=".self::DEFAULT_PORT.";dbname=$database;charset=".self::DEFAULT_CHARSET;
    }
}