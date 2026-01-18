<?php
declare(strict_types=1);

namespace Din9xtrCloud\Controllers;

use Din9xtrCloud\Middlewares\CsrfMiddleware;
use Din9xtrCloud\Rclone\RcloneClient;
use Din9xtrCloud\Rclone\RcloneICloudConfigurator;
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
        private RcloneICloudConfigurator $configurator,
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

        $query = $request->getQueryParams();
        $show2fa = (bool)($query['show2fa'] ?? false);
        $appleId = (string)($query['apple_id'] ?? '');

        $error = '';
        if (isset($_SESSION['icloud_error'])) {
            $error = (string)$_SESSION['icloud_error'];
            unset($_SESSION['icloud_error']);
        }

        $title = $show2fa ? 'iCloud 2FA Verification' : 'iCloud Login';

        return View::display(
            new ICloudLoginViewModel(
                title: $title,
                csrf: CsrfMiddleware::generateToken($request),
                show2fa: $show2fa,
                error: $error,
                appleId: $appleId
            )
        );
    }

    public function submitCredentials(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        $user = $request->getAttribute('user');

        $appleId = (string)($data['apple_id'] ?? '');
        $password = (string)($data['password'] ?? '');
        $remote = 'icloud_' . $user->id;

        try {
            $this->configurator->createRemote($remote, $appleId);
            $this->configurator->setPassword($remote, $password);

            $config = $this->configurator->getConfig($remote);
            $trustToken = $config['trust_token'] ?? null;
            $cookies = $config['cookies'] ?? null;

            $this->repository->create(
                $user->id,
                $remote,
                $appleId,
                $trustToken,
                $cookies
            );

            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', '/icloud/connect?show2fa=1&apple_id=' . urlencode($appleId));

        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['icloud_error'] = $e->getMessage();

            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', '/icloud/connect');
        }
    }


    public function submit2fa(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        $user = $request->getAttribute('user');

        $code = (string)($data['code'] ?? '');
        $appleId = (string)($data['apple_id'] ?? '');
        $remote = 'icloud_' . $user->id;

        try {
            $this->configurator->submit2fa($remote, $code);

            $account = $this->repository->findByUserId($user->id);
            if ($account) {
                $this->repository->update($account, [
                    'status' => 'connected',
                    'connected_at' => time()
                ]);
            }

            $contents = $this->rclone->call('operations/list', [
                'fs' => $remote . ':',
                'remote' => '',
                'opt' => [],
                'recurse' => true,
                'dirsOnly' => false,
                'filesOnly' => false,
                'metadata' => true,
            ]);

            $this->logger->info('iCloud contents', ['user' => $user->id, '$contents' => $contents]);

            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', '/storage');

        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['icloud_error'] = $e->getMessage();

            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', '/icloud/connect?' . http_build_query([
                        'show2fa' => 1,
                        'apple_id' => $appleId
                    ]));
        }
    }

}