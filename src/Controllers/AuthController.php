<?php
declare(strict_types=1);

namespace Din9xtrCloud\Controllers;

use Din9xtrCloud\Middlewares\CsrfMiddleware;
use Din9xtrCloud\Services\LoginService;
use Din9xtrCloud\View;
use Din9xtrCloud\ViewModels\Login\LoginViewModel;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class AuthController
{

    public function __construct(
        private LoginService    $loginService,
        private LoggerInterface $logger)
    {
    }

    public function loginForm(): string
    {
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        return View::display(new LoginViewModel(
            title: 'Login',
            error: $error,
            csrf: CsrfMiddleware::generateToken()
        ));
    }

    public function loginSubmit(ServerRequestInterface $request): Response
    {
        $data = (array)($request->getParsedBody() ?? []);

        $username = (string)($data['username'] ?? '');
        $password = (string)($data['password'] ?? '');

        $ip = getClientIp();
        $ua = $request->getHeaderLine('User-Agent') ?: null;

        $this->logger->info('Login submitted', [
            'username' => $username,
            'ip' => $ip,
        ]);

        $authToken = $this->loginService->attemptLogin(
            $username,
            $password,
            $ip,
            $ua
        );

        if ($authToken !== null) {
            session_regenerate_id(true);

            return new Response(
                302,
                [
                    'Location' => '/',
                    'Set-Cookie' => sprintf(
                        'auth_token=%s; HttpOnly; SameSite=Strict; Path=/; Secure',
                        $authToken
                    ),
                ]
            );
        }

        $_SESSION['login_error'] = 'Invalid credentials';

        return new Response(302, ['Location' => '/login']);
    }

    public function logout(ServerRequestInterface $request): Response
    {
        $token = $request->getCookieParams()['auth_token'] ?? null;

        if ($token) {
            $this->loginService->logout($token);
        }

        session_destroy();

        return new Response(
            302,
            [
                'Location' => '/login',
                'Set-Cookie' =>
                    'auth_token=deleted; expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/; HttpOnly'
            ]
        );
    }
}
