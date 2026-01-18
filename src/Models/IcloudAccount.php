<?php
declare(strict_types=1);

namespace Din9xtrCloud\Models;

final readonly class IcloudAccount
{
    public function __construct(
        public int    $id,
        public int    $userId,
        public string $remoteName,

        public string $appleId,
        public string $password,

        public string $trustToken,
        public string $cookies,

        public string $status,
        public int    $connectedAt,
        public int    $createdAt,
    )
    {
    }
}
