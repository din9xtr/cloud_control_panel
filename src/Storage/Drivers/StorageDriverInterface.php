<?php
declare(strict_types=1);

namespace Din9xtrCloud\Storage\Drivers;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

interface StorageDriverInterface
{
    public function getTotalBytes(int|string $userId): int;

    public function getUsedBytes(int|string $userId): int;

    /** @return array<string, int> */
    public function getUsedBytesByCategory(int|string $userId): array;

    public function createFolder(int|string $userId, string $name): void;

    public function storeUploadedFile(
        int|string            $userId,
        string                $folder,
        UploadedFileInterface $file,
        string                $targetName
    ): void;

    public function getUserFolderPath(int|string $userId, string $folder): string;

    /**
     * Initialize TUS upload
     *
     * @param int|string $userId
     * @param string $uploadId
     * @param int $size
     * @param array<string, string> $metadata folder
     */
    public function tusInit(
        int|string $userId,
        string     $uploadId,
        int        $size,
        array      $metadata
    ): void;

    public function tusWriteChunk(
        int|string      $userId,
        string          $uploadId,
        int             $offset,
        StreamInterface $stream
    ): int;

    /**
     * Get TUS upload status
     *
     * @param int|string $userId
     * @param string $uploadId
     * @return array{
     *     size: int,
     *     offset: int,
     *     metadata: array<string, string>,
     *     created_at: string,
     *     expires_at: string|null
     * }
     */
    public function tusGetStatus(
        int|string $userId,
        string     $uploadId
    ): array;

    public function getUserPath(int|string $userId): string;

    public function getFilePath(
        int|string $userId,
        string     $folder,
        string     $filename
    ): string;

    public function deleteFile(
        int|string $userId,
        string     $folder,
        string     $filename
    ): void;
}
