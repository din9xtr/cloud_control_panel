<?php
declare(strict_types=1);

namespace Din9xtrCloud\Controllers;

use Din9xtrCloud\Middlewares\CsrfMiddleware;
use Din9xtrCloud\Models\User;
use Din9xtrCloud\Storage\StorageService;
use Din9xtrCloud\Storage\UserStorageInitializer;
use Din9xtrCloud\View;
use Din9xtrCloud\ViewModels\Dashboard\DashboardViewModel;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class DashboardController
{
    public function __construct(
        private LoggerInterface        $logger,
        private StorageService         $storageService,
        private UserStorageInitializer $userStorageInitializer,
    )
    {
    }

    public function index(ServerRequestInterface $request): string
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute('user');

            $this->userStorageInitializer->init($user->id);

            $storage = $this->storageService->getStats($user);

            $folders = [];

            foreach ($storage->byFolder as $name => $bytes) {
                $percent = getStoragePercent(
                    $bytes,
                    $storage->totalBytes
                );

                $folders[] = [
                    'name' => $name,
                    'size' => formatBytes($bytes),
                    'percent' => $percent,
                ];
            }

            $this->logger->info('Dashboard loaded successfully');

        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            return View::display(new DashboardViewModel(
                title: 'Dashboard',
                username: $user->username,
                stats: [
                    'storage' => [
                        'total' => formatBytes(0),
                        'used' => formatBytes(0),
                        'free' => formatBytes(0),
                        'percent' => 0,
                        'folders' => [],
                    ],
                ],
                csrf: CsrfMiddleware::generateToken(),
            ));
        }

        return View::display(new DashboardViewModel(
            title: 'Dashboard',
            username: $user->username,
            stats: [
                'storage' => [
                    'total' => formatBytes($storage->totalBytes),
                    'used' => formatBytes($storage->usedBytes),
                    'free' => formatBytes($storage->freeBytes),
                    'percent' => $storage->percent,
                    'folders' => $folders,
                ],
            ],
            csrf: CsrfMiddleware::generateToken(),
        ));
    }
}