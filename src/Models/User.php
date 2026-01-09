<?php
declare(strict_types=1);

namespace Din9xtrCloud\Models;

final class User
{
    public int $id {
        get {
            return $this->id;
        }
        set (int $id) {
            $this->id = $id;
        }
    }

    public string $username {
        get {
            return $this->username;
        }
        set (string $username) {
            $this->username = $username;
        }
    }

    public string $passwordHash {
        get {
            return $this->passwordHash;
        }
        set (string $passwordHash) {
            $this->passwordHash = $passwordHash;
        }
    }

    public int $createdAt {
        get {
            return $this->createdAt;
        }
        set (int $createdAt) {
            $this->createdAt = $createdAt;
        }
    }

    public function __construct(
        int    $id,
        string $username,
        string $passwordHash,
        int    $createdAt,
    )
    {
        $this->id = $id;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}