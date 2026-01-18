<?php
declare(strict_types=1);

return function (PDO $db): void {
    $db->exec("PRAGMA foreign_keys = ON;");

    $db->exec("
        CREATE TABLE IF NOT EXISTS icloud_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            remote_name TEXT NOT NULL UNIQUE,

            apple_id TEXT NOT NULL,
            password TEXT NOT NULL,

            trust_token TEXT NOT NULL,
            cookies TEXT NOT NULL,

            status TEXT NOT NULL DEFAULT 'connected',
            connected_at INTEGER NOT NULL,
            created_at INTEGER NOT NULL,

            FOREIGN KEY(user_id) REFERENCES users(id)
        );
    ");
};
