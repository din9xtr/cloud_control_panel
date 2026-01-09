<?php
declare(strict_types=1);

namespace Din9xtrCloud\Models;
final readonly class Session
{
    public function __construct(
        public string  $id,
        public int     $userId,
        public string  $authToken,
        public ?string $ip,
        public ?string $userAgent,
        public int     $createdAt,
        public int     $lastActivityAt,
        public ?int    $revokedAt,
    )
    {
    }
}
