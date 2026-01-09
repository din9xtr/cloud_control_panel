<?php
declare(strict_types=1);

namespace Din9xtrCloud\Storage;

use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class StorageGuard
{
    public function __construct(private LoggerInterface $logger)
    {

    }

    public function assertEnoughSpace(string $path, int $bytes): void
    {
        $free = disk_free_space($path);

        if ($free !== false && $free < $bytes) {

            $this->logger->warning("Physical disk is full", ['path' => $path]);

            throw new RuntimeException('Physical disk is full');
        }
    }
}
