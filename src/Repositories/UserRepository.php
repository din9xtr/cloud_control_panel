<?php
declare(strict_types=1);

namespace Din9xtrCloud\Repositories;

use Din9xtrCloud\Models\User;
use Din9xtrCloud\Repositories\Exceptions\RepositoryException;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

final readonly class UserRepository
{
    public function __construct(
        private PDO             $db,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @param array<string, mixed> $criteria
     * @return User|null
     */
    public function findBy(array $criteria): ?User
    {
        try {
            if (empty($criteria)) {
                throw new \InvalidArgumentException('Criteria cannot be empty');
            }

            $whereParts = [];
            $params = [];

            foreach ($criteria as $field => $value) {
                $whereParts[] = "$field = :$field";
                $params[$field] = $value;
            }

            $whereClause = implode(' AND ', $whereParts);
            $sql = "SELECT * FROM users WHERE $whereClause LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                return null;
            }

            return new User(
                (int)$row['id'],
                $row['username'],
                $row['password'],
                (int)$row['created_at']
            );
        } catch (PDOException $e) {
            $this->logger->error('Failed to fetch user by criteria', [
                'criteria' => $criteria,
                'exception' => $e,
            ]);

            throw new RepositoryException(
                'Failed to fetch user',
                previous: $e
            );
        }
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findBy(['username' => $username]);
    }

    public function findById(int $id): ?User
    {
        return $this->findBy(['id' => $id]);
    }

}
