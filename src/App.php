<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace Din9xtrCloud;

use Din9xtrCloud\Container\Container;
use Nyholm\Psr7\Stream;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Relay\Relay;

final class App
{
    private Container $container;
    /**
     * @var array<int, string|callable|MiddlewareInterface>
     */
    private array $middlewares = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string|callable|MiddlewareInterface ...$middleware
     * @return self
     */
    public function middleware(...$middleware): self
    {
        foreach ($middleware as $m) {
            $this->middlewares[] = $m;
        }
        return $this;
    }

    public function router(Router $router): self
    {
        $this->middlewares[] = fn(ServerRequestInterface $request, callable $handler) => $router->dispatch($request);
        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function dispatch(): void
    {
        $request = $this->container->get(ServerRequestInterface::class);

        $resolver = fn($entry) => is_string($entry)
            ? $this->container->get($entry)
            : $entry;

        $pipeline = new Relay($this->middlewares, $resolver);

        $response = $pipeline->handle($request);

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read(8192);
            flush();
        }
        if ($body instanceof Stream) {
            $meta = $body->getMetadata();
            if (!empty($meta['uri']) && str_ends_with($meta['uri'], '.zip')) {
                @unlink($meta['uri']);
            }
        }
    }
}