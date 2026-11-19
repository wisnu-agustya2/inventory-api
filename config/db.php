<?php

// Default SQLite agar bisa langsung dicoba. Untuk MySQL, set environment variable:
// DB_DSN="mysql:host=127.0.0.1;dbname=inventory" DB_USER=root DB_PASS=secret
return [
    'class' => \yii\db\Connection::class,
    'dsn' => getenv('DB_DSN') ?: 'sqlite:' . dirname(__DIR__) . '/runtime/inventory.db',
    'username' => getenv('DB_USER') ?: null,
    'password' => getenv('DB_PASS') ?: null,
    'charset' => 'utf8mb4',
];
