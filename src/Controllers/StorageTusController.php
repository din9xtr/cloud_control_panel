<?php
declare(strict_types=1);

namespace Din9xtrCloud\Controllers;

use Din9xtrCloud\Models\User;
use Din9xtrCloud\Storage\StorageService;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class StorageTusController
{
    public function __construct(
        private StorageService  $storage,
        private LoggerInterface $logger
    )
    {
    }

    public function handle(ServerRequestInterface $request): Response
    {
        /** @var User|null $user */
        $user = $request->getAttribute('user');
        if (!$user) {
            return new Response(401);
        }

        return match ($request->getMethod()) {
            'POST' => $this->create($request, $user),
            'OPTIONS' => $this->options(),
            default => new Response(405),
        };
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    private function create(ServerRequestInterface $request, User $user): Response
    {
        $length = (int)$request->getHeaderLine('Upload-Length');
        if ($length <= 0) {
            return new Response(400);
        }

        $metadata = $this->parseMetadata(
            $request->getHeaderLine('Upload-Metadata')
        );

        $uploadId = bin2hex(random_bytes(16));

        $this->logger->info('CREATE_METADATA', $metadata);

        $this->storage->initTusUpload(
            user: $user,
            uploadId: $uploadId,
            size: $length,
            metadata: $metadata,
        );
        $this->logger->debug('ID: ' . $uploadId);
        return new Response(
            201,
            [
                'Tus-Resumable' => '1.0.0',
                'Location' => '/storage/tus/' . $uploadId,
            ]
        );
    }

    public function patch(
        ServerRequestInterface $request,
        ?string                $id
    ): Response
    {
        $user = $request->getAttribute('user');


        if (!$id) {
            return new Response(404);
        }

        $offset = (int)$request->getHeaderLine('Upload-Offset');
        $body = $request->getBody();

        $written = $this->storage->writeTusChunk(
            user: $user,
            uploadId: $id,
            offset: $offset,
            stream: $body
        );

        return new Response(
            204,
            [
                'Tus-Resumable' => '1.0.0',
                'Upload-Offset' => (string)($offset + $written),
            ]
        );
    }

    public function head(
        ServerRequestInterface $request,
        ?string                $id
    ): Response
    {
        $user = $request->getAttribute('user');

        if (!$id) {
            return new Response(404);
        }

        $status = $this->storage->getTusStatus($user, $id);

        return new Response(
            200,
            [
                'Tus-Resumable' => '1.0.0',
                'Upload-Offset' => (string)$status['offset'],
                'Upload-Length' => (string)$status['size'],
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    private function parseMetadata(string $raw): array
    {
        $result = [];
        foreach (explode(',', $raw) as $item) {
            if (!str_contains($item, ' ')) continue;
            [$k, $v] = explode(' ', $item, 2);
            $result[$k] = base64_decode($v);
        }
        return $result;
    }

    private function options(): Response
    {
        return new Response(
            204,
            [
                'Tus-Resumable' => '1.0.0',
                'Tus-Version' => '1.0.0',
                'Tus-Extension' => 'creation,creation-defer-length',
                'Tus-Max-Size' => (string)(1024 ** 4),
                'Access-Control-Allow-Methods' => 'POST, PATCH, HEAD, OPTIONS',
                'Access-Control-Allow-Headers' =>
                    'Tus-Resumable, Upload-Length, Upload-Offset, Upload-Metadata, Content-Type',
            ]
        );
    }

}