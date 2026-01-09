<?php
declare(strict_types=1);

return function (PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id TEXT PRIMARY KEY,
            user_id INTEGER NOT NULL,
            auth_token TEXT NOT NULL UNIQUE,
            ip TEXT,
            user_agent TEXT,
            created_at INTEGER NOT NULL,
            last_activity_at INTEGER NOT NULL,
            revoked_at INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ");
};
