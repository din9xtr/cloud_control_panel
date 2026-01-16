<?php
declare(strict_types=1);

namespace Din9xtrCloud\Middlewares;

use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const array UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const int TTL = 3600;

    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        if ($this->isExcludedPath($path)) {
            return $handler->handle($request);
        }

        if (in_array($method, self::UNSAFE_METHODS, true)) {
            $token = $request->getParsedBody()['_csrf'] ?? null;

            if (!$token || !self::validateToken($token, $request)) {
                return new Psr17Factory()
                    ->createResponse(403)
                    ->withBody(
                        new Psr17Factory()
                            ->createStream('CSRF validation failed')
                    );
            }
        }

        return $handler->handle($request);
    }

    private function isExcludedPath(string $path): bool
    {
        return
            str_starts_with($path, '/storage/tus') ||
            str_starts_with($path, '/api/') ||
            str_starts_with($path, '/webhook/');
    }

    public static function generateToken(ServerRequestInterface $request): string
    {
        try {
            $payload = json_encode([
                'ts' => time(),
                'ua' => hash('sha256', $request->getHeaderLine('User-Agent')),
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            error_log($e->getMessage());
        }

        $payloadB64 = base64_encode($payload);
        $signature = hash_hmac('sha256', $payloadB64, self::key(), true);

        return $payloadB64 . '.' . base64_encode($signature);

    }

    public static function validateToken(
        string                 $token,
        ServerRequestInterface $request
    ): bool
    {
        [$payloadB64, $sigB64] = explode('.', $token, 2) + [null, null];

        if (!$payloadB64 || !$sigB64) {
            return false;
        }

        $expected = hash_hmac('sha256', $payloadB64, self::key(), true);
        if (!hash_equals($expected, base64_decode($sigB64))) {
            return false;
        }

        $payload = json_decode(base64_decode($payloadB64), true);
        if (!isset($payload['ts'], $payload['ua'])) {
            return false;
        }

        if (time() - $payload['ts'] > self::TTL) {
            return false;
        }

        $uaHash = hash('sha256', $request->getHeaderLine('User-Agent'));

        return hash_equals($payload['ua'], $uaHash);
    }

    private static function key(): string
    {
        return $_ENV['APP_KEY']
            ?? throw new RuntimeException('APP_KEY not defined');
    }
}
