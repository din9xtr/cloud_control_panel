<?php
declare(strict_types=1);

namespace Din9xtrCloud\Controllers;

use Din9xtrCloud\Middlewares\CsrfMiddleware;
use Din9xtrCloud\Models\User;
use Din9xtrCloud\Storage\StorageService;
use Din9xtrCloud\View;
use Din9xtrCloud\ViewModels\Folder\FolderViewModel;
use JsonException;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Throwable;

final readonly class StorageController
{
    public function __construct(
        private StorageService  $storageService,
        private LoggerInterface $logger
    )
    {
    }

    public function createFolder(ServerRequestInterface $request): Response
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute('user');

            /** @var array<string, mixed>|null $data */
            $data = $request->getParsedBody();
            $name = trim($data['name'] ?? '');

            if ($name === '' || !preg_match('/^[a-zA-Z\x{0400}-\x{04FF}0-9_\- ]+$/u', $name)) {
                return new Response(302, ['Location' => '/']);
            }


            $this->storageService->createFolder($user, $name);

            /** @phpstan-ignore catch.neverThrown */
        } catch (Throwable $e) {
            $this->logger->error('Failed to save folder: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e
            ]);
            return new Response(302, ['Location' => '/?error=save_failed']);
        }

        return new Response(302, ['Location' => '/']);
    }

    /**
     * @throws JsonException
     */
    public function uploadFile(ServerRequestInterface $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        /** @var array<string, mixed>|null $data */
        $data = $request->getParsedBody();
        $folder = trim($data['folder'] ?? '');

        $file = $request->getUploadedFiles()['file'] ?? null;

        if (!$file instanceof UploadedFileInterface) {
            return $this->jsonError('no_file', 400);
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $this->jsonError('upload_failed', 422);
        }

        try {
            $this->storageService->uploadFile($user, $folder, $file);

            $this->logger->info('File uploaded', [
                'user_id' => $user->id,
                'folder' => $folder,
                'size' => $file->getSize(),
            ]);

            return $this->jsonSuccess(['code' => 'file_uploaded']);

        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            return $this->jsonError('save_failed', 500);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @throws JsonException
     */
    private function jsonSuccess(array $payload = []): Response
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['success' => true] + $payload, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @throws JsonException
     */
    private function jsonError(string $code, int $status): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode([
                'success' => false,
                'error' => $code,
            ], JSON_THROW_ON_ERROR)
        );
    }

    public function showFolder(ServerRequestInterface $request, string $folder): string
    {
        $user = $request->getAttribute('user');
        $csrfToken = CsrfMiddleware::generateToken();

        $folderPath = $this->storageService->getDriver()->getUserFolderPath($user->id, $folder);

        $files = [];
        $totalBytes = 0;
        $lastModified = null;

        if (is_dir($folderPath)) {
            foreach (scandir($folderPath) as $entry) {
                if (in_array($entry, ['.', '..'])) continue;

                $fullPath = $folderPath . '/' . $entry;
                if (!is_file($fullPath)) continue;

                $size = filesize($fullPath);
                $modifiedTime = filemtime($fullPath);

                if ($size === false || $modifiedTime === false) {
                    continue;
                }

                $modified = date('Y-m-d H:i:s', $modifiedTime);

                $files[] = [
                    'name' => $entry,
                    'size' => $this->humanFileSize($size),
                    'modified' => $modified,
                ];

                $totalBytes += $size;
                if ($lastModified === null || $modifiedTime > strtotime($lastModified)) {
                    $lastModified = $modified;
                }
            }
        }

        return View::display(new FolderViewModel(
            title: $folder,
            files: $files,
            csrf: $csrfToken,
            totalSize: $this->humanFileSize($totalBytes),
            lastModified: $lastModified ?? '—'
        ));
    }

    private function humanFileSize(int $bytes): string
    {
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        if ($bytes === 0) return '0 B';
        $factor = floor(log($bytes, 1024));
        return sprintf("%.2f %s", $bytes / (1024 ** $factor), $sizes[(int)$factor]);
    }

    public function deleteFolder(ServerRequestInterface $request, string $folder): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $this->storageService->deleteFolder($user, $folder);

        return new Response(302, ['Location' => '/']);
    }

    public function downloadFile(ServerRequestInterface $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $query = $request->getQueryParams();
        $folder = $query['folder'] ?? '';
        $file = $query['file'] ?? '';
        $this->logger->info('Downloading file', ['user_id' => $user->id, 'folder' => $folder, 'file' => $file, 'query' => $query]);

        try {
            $path = $this->storageService->getFileForDownload($user, $folder, $file);
            $this->logger->info('File downloaded', ['path' => $path]);

            $filename = basename($path);

            $mimeType = mime_content_type($path) ?: 'application/octet-stream';

            $fileSize = filesize($path);

            $fileStream = fopen($path, 'rb');
            if ($fileStream === false) {
                $this->logger->error('Cannot open file for streaming', ['path' => $path]);
                return new Response(500);
            }
            $stream = Stream::create($fileStream);

            return new Response(
                200,
                [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    'Content-Length' => (string)$fileSize,
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'Accept-Ranges' => 'bytes',
                ],
                $stream
            );
        } catch (Throwable $e) {
            $this->logger->warning('Download failed', [
                'user_id' => $user->id,
                'file' => $file,
                'exception' => $e,
            ]);

            return new Response(404);
        }
    }

    public function deleteFile(ServerRequestInterface $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        /** @var array<string, mixed> $data */
        $data = $request->getParsedBody();

        $folder = $data['folder'] ?? '';
        $file = $data['file_name'] ?? '';

        $this->storageService->deleteFile($user, $folder, $file);

        $this->logger->info('File deleted', [
            'user_id' => $user->id,
            'folder' => $folder,
            'file' => $file,
        ]);

        return new Response(
            302,
            ['Location' => '/folders/' . rawurlencode($folder)]
        );

    }

    /**
     * @throws JsonException
     * @throws RandomException
     */
    public function downloadMultiple(ServerRequestInterface $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $query = $request->getQueryParams();

        $folder = $query['folder'] ?? '';
        $raw = $query['file_names'] ?? '[]';

        $fileNames = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!$fileNames || !is_array($fileNames)) {
            return new Response(400);
        }

        $zipPath = $this->storageService->buildZipForDownload(
            user: $user,
            folder: $folder,
            files: $fileNames
        );
        if (!file_exists($zipPath)) {
            return new Response(404);
        }

        $fileStream = @fopen($zipPath, 'rb');
        if ($fileStream === false) {
            $this->logger->error('Cannot open zip file for streaming', ['path' => $zipPath]);
            return new Response(500);
        }
        $stream = Stream::create($fileStream);

        return new Response(
            200,
            [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="files.zip"',
                'Content-Length' => (string)filesize($zipPath),
                'Cache-Control' => 'no-store',
            ],
            $stream
        );
    }

    /**
     * @throws JsonException
     */
    public function deleteMultiple(ServerRequestInterface $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $data = $request->getParsedBody();

        if (!is_array($data)) {
            return new Response(400);
        }
        
        $folder = $data['folder'] ?? '';
        $raw = $data['file_names'] ?? '[]';

        $files = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $this->storageService->deleteMultipleFiles($user, $folder, $files);

        return new Response(
            302,
            ['Location' => '/folders/' . rawurlencode($folder)]
        );
    }

}
