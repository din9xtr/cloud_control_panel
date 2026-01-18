<?php
declare(strict_types=1);

namespace Din9xtrCloud\Controllers;

use Din9xtrCloud\Middlewares\CsrfMiddleware;
use Din9xtrCloud\Rclone\RcloneClient;
use Din9xtrCloud\Repositories\IcloudAccountRepository;
use Din9xtrCloud\View;
use Din9xtrCloud\ViewModels\Icloud\ICloudLoginViewModel;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class ICloudAuthController
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LoggerInterface          $logger,
        private IcloudAccountRepository  $repository,
        private RcloneClient             $rclone,
    )
    {
    }

    public function connectForm(ServerRequestInterface $request): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $error = $_SESSION['icloud_error'] ?? '';
        unset($_SESSION['icloud_error']);

        return View::display(
            new ICloudLoginViewModel(
                title: 'Connect iCloud',
                csrf: CsrfMiddleware::generateToken($request),
                error: $error
            )
        );
    }

    public function submit(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        $user = $request->getAttribute('user');

        $appleId = trim((string)($data['apple_id'] ?? ''));
        $password = trim((string)($data['password'] ?? ''));
        $cookies = trim((string)($data['cookies'] ?? ''));
        $trustToken = trim((string)($data['trust_token'] ?? ''));

        if ($appleId === '' || $password === '' || $cookies === '' || $trustToken === '') {
            return $this->fail('All fields are required');
        }

        $remote = 'icloud_' . $user->id;

        try {
            $encryptedAppleId = encryptString($appleId);
            $encryptedPassword = encryptString($password);

            try {
                $this->rclone->call('config/create', [
                    'name' => $remote,
                    'type' => 'iclouddrive',
                    'parameters' => [
                        'apple_id' => $appleId,
                        'password' => $password,
                        'cookies' => $cookies,
                        'trust_token' => $trustToken,
                    ],
                    'nonInteractive' => true,
                ]);
            } catch (Throwable $e) {
                $this->logger->warning('iCloud remote exists, updating', [
                    'remote' => $remote,
                    'exception' => $e,
                ]);

                $this->rclone->call('config/update', [
                    'name' => $remote,
                    'parameters' => [
                        'apple_id' => $appleId,
                        'password' => $password,
                        'cookies' => $cookies,
                        'trust_token' => $trustToken,
                    ],
                ]);
            }

            // health-check
            $list = $this->rclone->call('operations/list', [
                'fs' => $remote . ':',
                'remote' => '',
                'maxDepth' => 1,
            ]);

            $this->logger->info('icloud list', $list);

            $this->repository->createOrUpdate(
                userId: $user->id,
                remoteName: $remote,
                appleId: $encryptedAppleId,
                password: $encryptedPassword,
                trustToken: $trustToken,
                cookies: $cookies,
            );

            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', '/storage');

        } catch (Throwable $e) {
            $this->logger->error('iCloud connect failed', [
                'remote' => $remote,
                'exception' => $e,
            ]);

            return $this->fail($e->getMessage());
        }
    }


    private function fail(string $message): ResponseInterface
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['icloud_error'] = $message;

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', '/icloud/connect');
    }
}
