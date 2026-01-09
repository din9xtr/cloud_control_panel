<?php
declare(strict_types=1);

namespace Din9xtrCloud\Repositories;

use Din9xtrCloud\Models\Session;
use Din9xtrCloud\Repositories\Exceptions\RepositoryException;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Throwable;

final readonly class SessionRepository
{
    public function __construct(
        private PDO             $db,
        private LoggerInterface $logger
    )
    {
    }

    public function create(
        int     $userId,
        ?string $ip,
        ?string $userAgent
    ): Session
    {
        try {
            $id = bin2hex(random_bytes(16));
            $token = bin2hex(random_bytes(32));
            $now = time();

            $stmt = $this->db->prepare("
                INSERT INTO sessions (
                    id, user_id, auth_token, ip, user_agent,
                    created_at, last_activity_at
                ) VALUES (
                    :id, :user_id, :auth_token, :ip, :user_agent,
                    :created_at, :last_activity_at
                )
            ");

            $stmt->execute([
                'id' => $id,
                'user_id' => $userId,
                'auth_token' => $token,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'created_at' => $now,
                'last_activity_at' => $now,
            ]);

            return new Session(
                $id,
                $userId,
                $token,
                $ip,
                $userAgent,
                $now,
                $now,
                null
            );
        } catch (PDOException $e) {
            $this->logger->critical('Failed to create session', [
                'user_id' => $userId,
                'ip' => $ip,
                'exception' => $e,
            ]);

            throw new RepositoryException(
                'Failed to create session',
                previous: $e
            );
        } catch (RandomException $e) {
            $this->logger->critical('Failed to create session', [
                'user_id' => $userId,
                'ip' => $ip,
                'exception' => $e,
            ]);
            throw new RepositoryException(
                'Failed to revoke session',
                previous: $e
            );
        }
    }

    public function revokeByToken(string $token): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE sessions
                SET revoked_at = :revoked_at
                WHERE auth_token = :token AND revoked_at IS NULL
            ");

            $stmt->execute([
                'token' => $token,
                'revoked_at' => time(),
            ]);
        } catch (PDOException $e) {
            $this->logger->error('Failed to revoke session', [
                'token' => $token,
                'exception' => $e,
            ]);

            throw new RepositoryException(
                'Failed to revoke session',
                previous: $e
            );
        }
    }

    public function findActiveByToken(string $token): ?Session
    {
        try {
            $stmt = $this->db->prepare("
            SELECT *
            FROM sessions
            WHERE auth_token = :token
              AND revoked_at IS NULL
            LIMIT 1
        ");

            $stmt->execute(['token' => $token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row === false) {
                return null;
            }

            return new Session(
                $row['id'],
                (int)$row['user_id'],
                $row['auth_token'],
                $row['ip'],
                $row['user_agent'],
                (int)$row['created_at'],
                (int)$row['last_activity_at'],
                $row['revoked_at'] !== null ? (int)$row['revoked_at'] : null
            );
        } catch (Throwable $e) {
            $this->logger->error('Failed to fetch session by token', [
                'exception' => $e,
            ]);

            throw new RepositoryException(
                'Failed to fetch session',
                previous: $e
            );
        }
    }
}
