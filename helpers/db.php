<?php
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
            getenv("DB_HOST"),
            getenv("DB_PORT") ?: "5432",
            getenv("DB_NAME"),
            getenv("DB_SSLMODE") ?: "prefer"
        );
        $pdo = new PDO($dsn, getenv("DB_USER"), getenv("DB_PASS"), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
