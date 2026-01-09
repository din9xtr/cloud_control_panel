<?php
declare(strict_types=1);

namespace Din9xtrCloud\Storage;

final readonly class StorageStats
{
    public function __construct(
        public int   $totalBytes,
        public int   $usedBytes,
        public int   $freeBytes,
        public int   $percent,
        /** @var array<string, int> */
        public array $byFolder = [],
    )
    {
    }
}
