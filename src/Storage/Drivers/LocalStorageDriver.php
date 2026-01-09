<?php
declare(strict_types=1);

namespace Din9xtrCloud\Storage\Drivers;

use JsonException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use RuntimeException;

final readonly class LocalStorageDriver implements StorageDriverInterface
{
    public function __construct(
        private string $basePath,
        private int    $defaultLimitBytes,
    )
    {
    }

    public function getTotalBytes(int|string $userId): int
    {
        return $this->defaultLimitBytes;
    }

    public function getUsedBytes(int|string $userId): int
    {
        return array_sum($this->getUsedBytesByCategory($userId));
    }

    public function getUsedBytesByCategory(int|string $userId): array
    {
        $userPath = $this->basePath . '/users/' . $userId;

        if (!is_dir($userPath)) {
            return [];
        }

        $result = [];

        foreach (scandir($userPath) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === '.tus') {
                continue;
            }

            $fullPath = $userPath . '/' . $entry;

            if (!is_dir($fullPath)) {
                continue;
            }

            $result[$entry] = $this->getDirectorySize($fullPath);
        }

        return $result;
    }

    private function getDirectorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    public function createFolder(int|string $userId, string $name): void
    {
        $path = $this->basePath . '/users/' . $userId . '/' . $name;

        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0755, true);
    }

    public function getUserFolderPath(int|string $userId, string $folder): string
    {
        return $this->basePath . '/users/' . $userId . '/' . $folder;
    }

    public function getUserPath(int|string $userId): string
    {
        return $this->basePath . '/users/' . $userId;
    }

    public function storeUploadedFile(
        int|string            $userId,
        string                $folder,
        UploadedFileInterface $file,
        string                $targetName
    ): void
    {
        $dir = $this->getUserFolderPath($userId, $folder);

        if (!is_dir($dir)) {
            throw new RuntimeException('Folder not found');
        }

        $file->moveTo($dir . '/' . $targetName);
    }

    private function tusDir(int|string $userId): string
    {
        return $this->basePath . '/users/' . $userId . '/.tus';
    }

    private function tusMeta(int|string $userId, string $id): string
    {
        return $this->tusDir($userId) . "/$id.meta";
    }

    private function tusBin(int|string $userId, string $id): string
    {
        return $this->tusDir($userId) . "/$id.bin";
    }

    /**
     * @throws JsonException
     */
    public function tusInit(
        int|string $userId,
        string     $uploadId,
        int        $size,
        array      $metadata
    ): void
    {
        $dir = $this->tusDir($userId);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            $this->tusMeta($userId, $uploadId),
            json_encode([
                'size' => $size,
                'offset' => 0,
                'metadata' => $metadata,
            ], JSON_THROW_ON_ERROR)
        );

        touch($this->tusBin($userId, $uploadId));
    }

    /**
     * @throws JsonException
     */
    public function tusWriteChunk(
        int|string      $userId,
        string          $uploadId,
        int             $offset,
        StreamInterface $stream
    ): int
    {
        $metaFile = $this->tusMeta($userId, $uploadId);
        $binFile = $this->tusBin($userId, $uploadId);

        if (!is_file($metaFile) || !is_file($binFile)) {
            throw new RuntimeException('Upload not found');
        }

        $metaContent = file_get_contents($metaFile);
        if ($metaContent === false) {
            throw new RuntimeException('Failed to read TUS metadata');
        }

        $meta = json_decode(
            $metaContent,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if ($offset !== $meta['offset']) {
            throw new RuntimeException('Invalid upload offset');
        }

        $fp = fopen($binFile, 'c+');
        if ($fp === false) {
            throw new RuntimeException('Failed to open binary file for writing');
        }

        if (fseek($fp, $offset) !== 0) {
            fclose($fp);
            throw new RuntimeException('Failed to seek to offset');
        }

        $detachedStream = $stream->detach();
        if ($detachedStream === null) {
            fclose($fp);
            throw new RuntimeException('Stream is already detached');
        }

        $written = stream_copy_to_stream($detachedStream, $fp);
        fclose($fp);

        if ($written === false) {
            throw new RuntimeException('Failed to write chunk');
        }

        $meta['offset'] += $written;

        if ($meta['offset'] >= $meta['size']) {
            $this->finalizeTusUpload($userId, $uploadId, $meta);
        } else {
            $updatedMeta = json_encode($meta, JSON_THROW_ON_ERROR);
            if (file_put_contents($metaFile, $updatedMeta) === false) {
                throw new RuntimeException('Failed to update TUS metadata');
            }
        }

        return $written;
    }

    /**
     * Finalize TUS upload
     *
     * @param int|string $userId
     * @param string $uploadId
     * @param array{
     *     size: int,
     *     offset: int,
     *     metadata: array<string, string>,
     *     created_at: string,
     *     expires_at: string
     * } $meta
     * @throws RuntimeException
     */
    private function finalizeTusUpload(
        int|string $userId,
        string     $uploadId,
        array      $meta
    ): void
    {
        $folder = $meta['metadata']['folder'] ?? 'default';
        $filename = $meta['metadata']['filename'] ?? $uploadId;

        $targetDir = $this->getUserFolderPath($userId, $folder);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new RuntimeException("Failed to create target directory: $targetDir");
            }
        }

        $targetFile = $targetDir . '/' . $filename;

        if (file_exists($targetFile)) {
            $filename = $this->addSuffixToFilename($filename, $targetDir);
            $targetFile = $targetDir . '/' . $filename;
        }

        $sourceFile = $this->tusBin($userId, $uploadId);

        if (!rename($sourceFile, $targetFile)) {
            throw new RuntimeException("Failed to move file from $sourceFile to $targetFile");
        }

        $metaFile = $this->tusMeta($userId, $uploadId);
        if (!unlink($metaFile)) {
            throw new RuntimeException("Failed to delete metadata file: $metaFile");
        }
    }

    private function addSuffixToFilename(string $filename, string $targetDir): string
    {
        $pathInfo = pathinfo($filename);
        $name = $pathInfo['filename'];
        $ext = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        $counter = 1;

        do {
            $newFilename = $name . '_' . $counter . $ext;
            $newPath = $targetDir . '/' . $newFilename;
            $counter++;
        } while (file_exists($newPath) && $counter < 100);

        return $newFilename;
    }

    /**
     * @throws JsonException
     */
    public function tusGetStatus(
        int|string $userId,
        string     $uploadId
    ): array
    {
        $metaFile = $this->tusMeta($userId, $uploadId);

        if (!is_file($metaFile)) {
            throw new RuntimeException('Upload not found');
        }

        $metaContent = file_get_contents($metaFile);
        if ($metaContent === false) {
            throw new RuntimeException('Failed to read TUS metadata');
        }

        $meta = json_decode(
            $metaContent,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return [
            'size' => (int)($meta['size'] ?? 0),
            'offset' => (int)($meta['offset'] ?? 0),
            'metadata' => $meta['metadata'] ?? [],
            'created_at' => $meta['created_at'] ?? date('c'),
            'expires_at' => $meta['expires_at'] ?? null,
        ];
    }

    public function getFilePath(
        int|string $userId,
        string     $folder,
        string     $filename
    ): string
    {
        $path = $this->getUserFolderPath($userId, $folder) . '/' . $filename;

        if (!is_file($path)) {
            throw new RuntimeException('File not found');
        }

        return $path;
    }

    public function deleteFile(
        int|string $userId,
        string     $folder,
        string     $filename
    ): void
    {
        $path = $this->getFilePath($userId, $folder, $filename);

        if (!unlink($path)) {
            throw new RuntimeException('Failed to delete file');
        }
    }
}
