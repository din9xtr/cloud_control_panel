<?php
declare(strict_types=1);

namespace Din9xtrCloud\Repositories;

use Din9xtrCloud\Models\IcloudAccount;
use PDO;

final readonly class IcloudAccountRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function createOrUpdate(
        int    $userId,
        string $remoteName,
        string $appleId,
        string $password,
        string $trustToken,
        string $cookies
    ): IcloudAccount
    {
        $existing = $this->findByUserId($userId);
        $now = time();

        if ($existing) {
            $stmt = $this->db->prepare("
                UPDATE icloud_accounts
                SET apple_id = :apple_id,
                    password = :password,
                    trust_token = :trust_token,
                    cookies = :cookies,
                    status = 'connected',
                    connected_at = :connected_at
                WHERE id = :id
            ");

            $stmt->execute([
                ':apple_id' => $appleId,
                ':password' => $password,
                ':trust_token' => $trustToken,
                ':cookies' => $cookies,
                ':connected_at' => $now,
                ':id' => $existing->id,
            ]);

            return new IcloudAccount(
                id: $existing->id,
                userId: $userId,
                remoteName: $remoteName,
                appleId: $appleId,
                password: $password,
                trustToken: $trustToken,
                cookies: $cookies,
                status: 'connected',
                connectedAt: $now,
                createdAt: $existing->createdAt,
            );
        }

        $stmt = $this->db->prepare("
            INSERT INTO icloud_accounts
                (user_id, remote_name, apple_id, password, trust_token, cookies, status, connected_at, created_at)
            VALUES
                (:user_id, :remote_name, :apple_id, :password, :trust_token, :cookies, 'connected', :connected_at, :created_at)
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':remote_name' => $remoteName,
            ':apple_id' => $appleId,
            ':password' => $password,
            ':trust_token' => $trustToken,
            ':cookies' => $cookies,
            ':connected_at' => $now,
            ':created_at' => $now,
        ]);

        return new IcloudAccount(
            id: (int)$this->db->lastInsertId(),
            userId: $userId,
            remoteName: $remoteName,
            appleId: $appleId,
            password: $password,
            trustToken: $trustToken,
            cookies: $cookies,
            status: 'connected',
            connectedAt: $now,
            createdAt: $now,
        );
    }

    public function findByUserId(int $userId): ?IcloudAccount
    {
        $stmt = $this->db->prepare("
            SELECT * FROM icloud_accounts WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new IcloudAccount(
            id: (int)$row['id'],
            userId: (int)$row['user_id'],
            remoteName: $row['remote_name'],
            appleId: $row['apple_id'],
            password: $row['password'],
            trustToken: $row['trust_token'],
            cookies: $row['cookies'],
            status: $row['status'],
            connectedAt: (int)$row['connected_at'],
            createdAt: (int)$row['created_at'],
        ) : null;
    }
}
