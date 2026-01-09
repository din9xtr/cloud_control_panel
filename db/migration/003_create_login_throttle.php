<?php
declare(strict_types=1);

return function (PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_throttle (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 1,
            last_attempt INTEGER NOT NULL
        );
    ");
};
