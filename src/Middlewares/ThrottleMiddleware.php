<?php /** @noinspection SqlDialectInspection */

namespace Din9xtrCloud\Middlewares;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PDO;

final class ThrottleMiddleware implements MiddlewareInterface
{
    private int $maxAttempts = 5;
    private int $lockTime = 300; // seconds

    public function __construct(private readonly PDO $db)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $request->getHeaderLine('X-Forwarded-For') ?: $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $ip = explode(',', $ip)[0];

        $stmt = $this->db->prepare("SELECT * FROM login_throttle WHERE ip = :ip ORDER BY id DESC LIMIT 1");
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = time();
        $blockedUntil = null;

        if ($row) {
            if ($row['attempts'] >= $this->maxAttempts && ($now - $row['last_attempt']) < $this->lockTime) {
                $blockedUntil = $row['last_attempt'] + $this->lockTime;
            }
        }

        if ($blockedUntil && $now < $blockedUntil) {
            return new Response(429, [], 'Too Many Requests');
        }

        $response = $handler->handle($request);

        if ($request->getUri()->getPath() === '/login') {
            $attempts = ($row['attempts'] ?? 0);
            if ($response->getStatusCode() === 302) {
                $this->db->prepare("
                    INSERT INTO login_throttle (ip, attempts, last_attempt)
                    VALUES (:ip, 0, :last_attempt)
                ")->execute(['ip' => $ip, 'last_attempt' => $now]);
            } else {
                $attempts++;
                $this->db->prepare("
                    INSERT INTO login_throttle (ip, attempts, last_attempt)
                    VALUES (:ip, :attempts, :last_attempt)
                ")->execute([
                    'ip' => $ip,
                    'attempts' => $attempts,
                    'last_attempt' => $now
                ]);
            }
        }
        return $response;
    }
}
