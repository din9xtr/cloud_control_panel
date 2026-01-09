<?php
declare(strict_types=1);

return function (PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at INTEGER NOT NULL
        );
    ");
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM users WHERE username = :username"
    );
    $stmt->execute(['username' => getenv('USER')]);

    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    $passwordHash = password_hash(
        getenv('PASSWORD'),
        PASSWORD_DEFAULT
    );

    $stmt = $db->prepare("
        INSERT INTO users (username, password, created_at)
        VALUES (:username, :password, :created_at)
    ");

    $stmt->execute([
        'username' => $_ENV['USER'],
        'password' => $passwordHash,
        'created_at' => time(),
    ]);
};