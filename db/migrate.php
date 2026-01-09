<?php
declare(strict_types=1);
$username = $_ENV['USER'] ?? null;
$password = $_ENV['PASSWORD'] ?? null;

if (!$username || !$password) {
    throw new RuntimeException('Environment variable "USER" or "PASSWORD" is missing');
}

$databasePath = __DIR__ . '/database.sqlite';
$migrationsPath = __DIR__ . '/migration';

if (!is_dir(__DIR__)) {
    mkdir(__DIR__, 0777, true);
}

$db = new PDO('sqlite:' . $databasePath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration TEXT NOT NULL UNIQUE,
    ran_at INTEGER NOT NULL
);
");

$migrations = glob($migrationsPath . '/*.php');
sort($migrations);

foreach ($migrations as $file) {
    $name = basename($file);

    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM migrations WHERE migration = :migration"
    );
    $stmt->execute(['migration' => $name]);

    if ((int)$stmt->fetchColumn() > 0) {
        continue;
    }

    echo "Running: $name\n";

    /** @var callable $migration */
    $migration = require $file;

    if (!is_callable($migration)) {
        throw new RuntimeException("Migration $name must return callable");
    }

    $db->beginTransaction();
    try {
        $migration($db);

        $stmt = $db->prepare("
            INSERT INTO migrations (migration, ran_at)
            VALUES (:migration, :ran_at)
        ");
        $stmt->execute([
            'migration' => $name,
            'ran_at' => time(),
        ]);

        $db->commit();
        echo "✔ Migrated: $name\n";
    } catch (Throwable $e) {
        $db->rollBack();
        echo "✖ Failed: $name\n";
        throw $e;
    }
}
