<?php
declare(strict_types=1);

namespace Din9xtrCloud\Middlewares;

use Din9xtrCloud\Repositories\SessionRepository;
use Din9xtrCloud\Repositories\UserRepository;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var array<int, string> Path, no need to auth check
     */
    private array $except = [
        '/login',
        '/logout',
        '/license'
    ];


    public function __construct(
        private readonly SessionRepository $sessions,
        private readonly UserRepository    $users,
    )
    {
    }

    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        $token = $request->getCookieParams()['auth_token'] ?? null;
        $session = null;

        if ($token) {
            $session = $this->sessions->findActiveByToken($token);
        }
        if ($path === '/login' && $session !== null) {
            return new Response(302, ['Location' => '/']);
        }

        if (in_array($path, $this->except, true)) {
            return $handler->handle($request);
        }
        if (!$token) {
            if ($request->getMethod() !== 'GET') {
                return new Response(401);
            }
            return new Response(302, ['Location' => '/login']);
        }
        if ($session === null) {
            return new Response(
                302,
                [
                    'Location' => '/login',
                    'Set-Cookie' =>
                        'auth_token=deleted; expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/'
                ]
            );
        }

        $request = $request->withAttribute('user', $this->users->findById($session->userId));
        $request = $request->withAttribute('session', $session);
        return $handler->handle($request);
    }
}
