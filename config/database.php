<?php
define('DB_HOST',   getenv('DB_HOST')   ?: 'localhost');
define('DB_PORT',   getenv('DB_PORT')   ?: '5432');
define('DB_NAME',   getenv('DB_NAME')   ?: 'bodegas_db');
define('DB_USER',   getenv('DB_USER')   ?: 'postgres');
define('DB_PASS',   getenv('DB_PASS')   ?: 'postgres');


function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
