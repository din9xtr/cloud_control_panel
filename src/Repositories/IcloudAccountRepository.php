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

    public function create(
        int     $userId,
        string  $remoteName,
        string  $appleId,
        ?string $trustToken = null,
        ?string $cookies = null
    ): IcloudAccount
    {
        $stmt = $this->db->prepare("
            INSERT INTO icloud_accounts (user_id, remote_name, apple_id, trust_token, cookies)
            VALUES (:user_id, :remote_name, :apple_id, :trust_token, :cookies)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':remote_name' => $remoteName,
            ':apple_id' => $appleId,
            ':trust_token' => $trustToken,
            ':cookies' => $cookies,
        ]);

        $id = (int)$this->db->lastInsertId();

        return new IcloudAccount(
            id: $id,
            userId: $userId,
            remoteName: $remoteName,
            appleId: $appleId,
            trustToken: $trustToken,
            cookies: $cookies,
            status: 'pending',
            connectedAt: null,
            createdAt: time(),
        );
    }

    public function update(
        IcloudAccount $account,
        array         $fields
    ): void
    {
        $set = [];
        $params = [':id' => $account->id];
        foreach ($fields as $key => $value) {
            $set[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $stmt = $this->db->prepare("UPDATE icloud_accounts SET " . implode(', ', $set) . " WHERE id = :id");
        $stmt->execute($params);
    }

    public function findByUserId(int $userId): ?IcloudAccount
    {
        $stmt = $this->db->prepare("SELECT * FROM icloud_accounts WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new IcloudAccount(
            id: (int)$row['id'],
            userId: (int)$row['user_id'],
            remoteName: $row['remote_name'],
            appleId: $row['apple_id'],
            trustToken: $row['trust_token'],
            cookies: $row['cookies'],
            status: $row['status'],
            connectedAt: $row['connected_at'] ? (int)$row['connected_at'] : null,
            createdAt: (int)$row['created_at'],
        ) : null;
    }
}
