<?php
declare(strict_types=1);

namespace Din9xtrCloud\Middlewares;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;
use RuntimeException;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const array UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        if ($this->isExcludedPath($path)) {
            return $handler->handle($request);
        }

        if (in_array($method, self::UNSAFE_METHODS, true)) {

            $token = $_POST['_csrf'] ?? '';
            if (!isset($_SESSION['_csrf']) || $token !== $_SESSION['_csrf']) {
                return new Psr17Factory()->createResponse(403)
                    ->withBody(new Psr17Factory()->createStream('CSRF validation failed'));
            }
        }

        return $handler->handle($request);
    }

    private function isExcludedPath(string $path): bool
    {
        if (str_starts_with($path, '/storage/tus')) {
            return true;
        }

        if (str_starts_with($path, '/api/')) {
            return true;
        }

        if (str_starts_with($path, '/webhook/')) {
            return true;
        }

        return false;
    }

    public static function generateToken(): string
    {
        if (empty($_SESSION['_csrf']) || $_SESSION['_csrf_expire'] < time()) {
            try {
                $_SESSION['_csrf'] = bin2hex(random_bytes(32));
                $_SESSION['_csrf_expire'] = time() + 3600;
            } catch (RandomException $e) {
                throw new RuntimeException(
                    'Failed to generate CSRF token: ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        return $_SESSION['_csrf'];
    }
}
