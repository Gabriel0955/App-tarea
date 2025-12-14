<?php
// src/db.php - Conexión PDO a PostgreSQL
require_once __DIR__ . '/../config.php';

function get_pdo()
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $host = DB_HOST;
    $db   = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    $port = DB_PORT;

    $dsn = "pgsql:host={$host};port={$port};dbname={$db};options='--client_encoding=UTF8'";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            die('DB connection failed: ' . $e->getMessage());
        }
        die('DB connection failed.');
    }
}
