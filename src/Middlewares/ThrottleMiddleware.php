<?php /** @noinspection SqlDialectInspection */

namespace Din9xtrCloud\Middlewares;

use Nyholm\Psr7\Response;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class ThrottleMiddleware implements MiddlewareInterface
{
    private const int MAX_ATTEMPTS = 10;
    private const int LOCK_TIME = 300;

    public function __construct(
        private PDO             $db,
        private LoggerInterface $logger,
    )
    {
    }

    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $ip = getClientIp();
        $now = time();

        $row = $this->getLastAttempt($ip);

        if ($row !== null) {
            $attempts = (int)$row['attempts'];
            $lastAttempt = (int)$row['last_attempt'];

            if ($now - $lastAttempt > self::LOCK_TIME) {
                $this->clearAttempts($ip);
                $this->logger->info('Throttle window expired, reset attempts', [
                    'ip' => $ip,
                ]);

                $row = null;
            } elseif ($attempts >= self::MAX_ATTEMPTS) {
                $retryAfter = ($lastAttempt + self::LOCK_TIME) - $now;

                $this->logger->warning('Login throttled', [
                    'ip' => $ip,
                    'attempts' => $attempts,
                    'retry_after' => $retryAfter,
                ]);

                return new Response(
                    429,
                    ['Retry-After' => (string)max(1, $retryAfter)],
                    'Too Many Requests'
                );
            }
        }

        $this->registerAttempt($ip, $now, $row !== null);

        return $handler->handle($request);
    }

    private function getLastAttempt(string $ip): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, attempts, last_attempt
             FROM login_throttle
             WHERE ip = :ip
             ORDER BY id DESC
             LIMIT 1"
        );

        $stmt->execute(['ip' => $ip]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function registerAttempt(string $ip, int $now, bool $exists): void
    {
        if ($exists) {
            $stmt = $this->db->prepare(
                "UPDATE login_throttle
                 SET attempts = attempts + 1,
                     last_attempt = :time
                 WHERE id = (
                     SELECT id FROM login_throttle
                     WHERE ip = :ip
                     ORDER BY id DESC
                     LIMIT 1
                 )"
            );

            $this->logger->info('Throttle attempt incremented', [
                'ip' => $ip,
            ]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO login_throttle (ip, attempts, last_attempt)
                 VALUES (:ip, 1, :time)"
            );

            $this->logger->info('Throttle first attempt registered', [
                'ip' => $ip,
            ]);
        }

        $stmt->execute([
            'ip' => $ip,
            'time' => $now,
        ]);
    }

    private function clearAttempts(string $ip): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM login_throttle WHERE ip = :ip"
        );

        $stmt->execute(['ip' => $ip]);
    }
}
