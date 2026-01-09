<?php
declare(strict_types=1);

namespace Din9xtrCloud\Storage;

use Din9xtrCloud\Models\User;
use Din9xtrCloud\Storage\Drivers\StorageDriverInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Random\RandomException;
use RuntimeException;
use ZipArchive;

final readonly class StorageService
{
    public function __construct(
        private StorageDriverInterface $driver,
        private StorageGuard           $guard,

    )
    {
    }

    public function getDriver(): StorageDriverInterface
    {
        return $this->driver;
    }

    public function getStats(User $user): StorageStats
    {
        $total = $this->driver->getTotalBytes($user->id);
        $used = $this->driver->getUsedBytes($user->id);
        $free = max(0, $total - $used);
        $percent = $total > 0
            ? (int)round(($used / $total) * 100)
            : 0;

        return new StorageStats(
            totalBytes: $total,
            usedBytes: $used,
            freeBytes: $free,
            percent: min(100, $percent),
            byFolder: $this->driver->getUsedBytesByCategory($user->id)
        );
    }

    public function createFolder(User $user, string $name): void
    {
        $this->driver->createFolder($user->id, $name);
    }

    public function uploadFile(
        User                  $user,
        string                $folder,
        UploadedFileInterface $file
    ): void
    {
        $this->assertValidFolderName($folder);

        $stats = $this->getStats($user);
        $fileSize = (int)$file->getSize();

        if ($stats->freeBytes < $fileSize) {
            throw new RuntimeException('Storage limit exceeded');
        }

        $folderPath = $this->driver->getUserPath($user->id);
        $this->guard->assertEnoughSpace($folderPath, $fileSize);

        $safeName = $this->generateSafeFilename(
            $file->getClientFilename() ?? 'file',
            $folderPath . '/' . $folder
        );
        $this->driver->storeUploadedFile(
            $user->id,
            $folder,
            $file,
            $safeName
        );
    }

    private function generateSafeFilename(string $original, string $folderPath): string
    {
        $original = $this->fixFileNameEncoding($original);
        $name = pathinfo($original, PATHINFO_FILENAME);
        $ext = pathinfo($original, PATHINFO_EXTENSION);

        $safe = preg_replace('/[\/:*?"<>|\\\\]/', '_', $name);

        if (empty($safe)) {
            $safe = 'file';
        }
        $baseName = $safe . ($ext ? '.' . $ext : '');
        $basePath = $folderPath . '/' . $baseName;

        if (!file_exists($basePath)) {
            error_log("Returning: " . $baseName);
            return $baseName;
        }

        $counter = 1;
        $maxAttempts = 100;

        do {
            $newFilename = $safe . '_' . $counter . ($ext ? '.' . $ext : '');
            $newPath = $folderPath . '/' . $newFilename;
            $counter++;
        } while (file_exists($newPath) && $counter <= $maxAttempts);

        return $newFilename;
    }

    private function assertValidFolderName(string $folder): void
    {
        if ($folder === '' || !preg_match('/^[a-zA-Z\x{0400}-\x{04FF}0-9_\- ]+$/u', $folder)) {
            throw new RuntimeException('Invalid folder');
        }
    }

    private function fixFileNameEncoding(string $filename): string
    {
        $detected = mb_detect_encoding($filename, ['UTF-8', 'Windows-1251', 'ISO-8859-5', 'KOI8-R'], true);

        if ($detected && $detected !== 'UTF-8') {
            $converted = mb_convert_encoding($filename, 'UTF-8', $detected);
            $filename = $converted !== false ? $converted : "unknown_file";
        }

        return $filename;
    }

    /**
     * Initialize TUS upload
     *
     * @param User $user
     * @param string $uploadId
     * @param int $size
     * @param array<string, string> $metadata folder
     * @throws RuntimeException
     */
    public function initTusUpload(
        User   $user,
        string $uploadId,
        int    $size,
        array  $metadata
    ): void
    {
        $stats = $this->getStats($user);

        $userFolderPath = $this->driver->getUserPath($user->id);

        $this->guard->assertEnoughSpace($userFolderPath, $size);

        if ($stats->freeBytes < $size) {
            throw new RuntimeException('Storage limit exceeded');
        }

        $this->driver->tusInit(
            userId: $user->id,
            uploadId: $uploadId,
            size: $size,
            metadata: $metadata
        );
    }

    /**
     * Write TUS chunk
     *
     * @param User $user
     * @param string $uploadId
     * @param int $offset
     * @param StreamInterface $stream
     * @return int Number of bytes written
     */
    public function writeTusChunk(
        User            $user,
        string          $uploadId,
        int             $offset,
        StreamInterface $stream
    ): int
    {
        return $this->driver->tusWriteChunk(
            userId: $user->id,
            uploadId: $uploadId,
            offset: $offset,
            stream: $stream
        );
    }

    /**
     * Get TUS upload status
     *
     * @param User $user
     * @param string $uploadId
     * @return array{
     *     size: int,
     *     offset: int,
     *     metadata: array<string, string>,
     *     created_at: string,
     *     expires_at: string|null
     * }
     */
    public function getTusStatus(User $user, string $uploadId): array
    {
        return $this->driver->tusGetStatus($user->id, $uploadId);
    }

    public function deleteFile(
        User   $user,
        string $folder,
        string $filename
    ): void
    {
        $this->assertValidFolderName($folder);

        if ($filename === '' || str_contains($filename, '..')) {
            throw new RuntimeException('Invalid filename');
        }

        $this->driver->deleteFile($user->id, $folder, $filename);
    }

    public function getFileForDownload(
        User   $user,
        string $folder,
        string $filename
    ): string
    {
        $this->assertValidFolderName($folder);

        if ($filename === '' || str_contains($filename, '..')) {
            throw new RuntimeException('Invalid filename');
        }

        return $this->driver->getFilePath($user->id, $folder, $filename);
    }

    /**
     * @param array<string> $files
     * @throws RandomException
     */
    public function buildZipForDownload(
        User   $user,
        string $folder,
        array  $files
    ): string
    {
        $this->assertValidFolderName($folder);

        $tmpDir = sys_get_temp_dir() . '/cloud-' . $user->id;
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0700, true);
        }

        $zipPath = $tmpDir . '/download-' . bin2hex(random_bytes(8)) . '.zip';


        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Cannot create zip');
        }

        foreach ($files as $file) {
            if ($file === '' || str_contains($file, '..')) {
                continue;
            }

            $path = $this->driver->getFilePath($user->id, $folder, $file);
            $zip->addFile($path, $file);
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * @param array<string> $files
     */
    public function deleteMultipleFiles(
        User   $user,
        string $folder,
        array  $files
    ): void
    {
        $this->assertValidFolderName($folder);

        foreach ($files as $file) {
            if ($file === '' || str_contains($file, '..')) {
                continue;
            }

            $path = $this->driver->getFilePath($user->id, $folder, $file);
            @unlink($path);
        }
    }

    public function deleteFolder(User $user, string $folder): void
    {
        $this->assertValidFolderName($folder);

        $path = $this->driver->getUserFolderPath($user->id, $folder);

        if (!is_dir($path)) {
            throw new RuntimeException("Folder not found: $folder");
        }
        $this->deleteDirectoryRecursive($path);
    }

    private function deleteDirectoryRecursive(string $dir): void
    {
        $items = scandir($dir);
        if ($items === false) {
            throw new RuntimeException("Failed to read directory: $dir");
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }


}
