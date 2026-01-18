<?php
declare(strict_types=1);

namespace Din9xtrCloud\Storage\Drivers;

use Din9xtrCloud\Rclone\RcloneClient;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

final readonly class ICloudStorageDriver implements StorageDriverInterface
{
    public function __construct(
        private RcloneClient $rclone,
        private string       $remoteName,
    )
    {
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function getUsedBytes(int|string $userId): int
    {
        $stats = $this->rclone->call('operations/about', [
            'fs' => $this->remoteName . ':',
        ]);

        return (int)($stats['used'] ?? 0);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function createFolder(int|string $userId, string $name): void
    {
        $this->rclone->call('operations/mkdir', [
            'fs' => $this->remoteName . ':',
            'remote' => $name,
        ]);
    }

    public function getTotalBytes(int|string $userId): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getUsedBytesByCategory(int|string $userId): array
    {
        return [];
    }

    public function storeUploadedFile(int|string $userId, string $folder, UploadedFileInterface $file, string $targetName): void
    {
        // TODO: Implement storeUploadedFile() method.
    }

    public function getUserFolderPath(int|string $userId, string $folder): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function tusInit(int|string $userId, string $uploadId, int $size, array $metadata): void
    {
        // TODO: Implement tusInit() method.
    }

    public function tusWriteChunk(int|string $userId, string $uploadId, int $offset, StreamInterface $stream): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function tusGetStatus(int|string $userId, string $uploadId): array
    {
        return [];
    }

    public function getUserPath(int|string $userId): string
    {
        return '';
    }

    public function getFilePath(int|string $userId, string $folder, string $filename): string
    {
        return '';
    }

    public function deleteFile(int|string $userId, string $folder, string $filename): void
    {
        // TODO: Implement deleteFile() method.
    }
}
