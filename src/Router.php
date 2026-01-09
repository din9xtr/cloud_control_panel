<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace Din9xtrCloud;

use Din9xtrCloud\ViewModels\Errors\ErrorViewModel;
use FastRoute\Dispatcher;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Relay\Relay;
use RuntimeException;
use Throwable;
use function FastRoute\simpleDispatcher;

final class Router
{
    private Dispatcher $dispatcher;
    /**
     * @var array<string, list<string|callable|MiddlewareInterface>>
     */
    private array $routeMiddlewares = [];

    public function __construct(
        callable                            $routes,
        private readonly ContainerInterface $container
    )
    {
        $this->dispatcher = simpleDispatcher($routes);
    }

    public function middlewareFor(string $path, string|callable|MiddlewareInterface ...$middlewares): self
    {
        /** @var list<string|callable|MiddlewareInterface> $middlewares */

        $this->routeMiddlewares[$path] = $middlewares;
        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $routeInfo = $this->dispatcher->dispatch(
                $request->getMethod(),
                rawurldecode($request->getUri()->getPath())
            );
            return match ($routeInfo[0]) {
                Dispatcher::NOT_FOUND => $this->createErrorResponse(404, '404 Not Found'),
                Dispatcher::METHOD_NOT_ALLOWED => $this->createErrorResponse(405, '405 Method Not Allowed'),
                Dispatcher::FOUND => $this->handleFoundRoute($request, $routeInfo[1], $routeInfo[2]),
            };
        } catch (Throwable $e) {

            if ($e instanceof NotFoundExceptionInterface) {
                throw new $e;
            }

            if ($e instanceof ContainerExceptionInterface) {
                throw new $e;
            }

            return $this->handleException($e);
        }
    }

    /**
     * @param string|callable|array{0: class-string, 1: string} $handler
     * @param array<string, string> $routeParams
     */
    private function handleFoundRoute(
        ServerRequestInterface $request,
        mixed                  $handler,
        array                  $routeParams
    ): ResponseInterface
    {
        foreach ($routeParams as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }
        $middlewares = $this->getMiddlewaresFor($request);

        $middlewares[] = fn(ServerRequestInterface $req, $next) => $this->ensureResponse($this->callHandler($req, $handler, $routeParams));


        $resolver = fn($entry) => is_string($entry) ? $this->container->get($entry) : $entry;

        $pipeline = new Relay($middlewares, $resolver);

        return $pipeline->handle($request);
    }

    /**
     * @return list<string|callable|MiddlewareInterface>
     */
    private function getMiddlewaresFor(ServerRequestInterface $request): array
    {
        $path = $request->getUri()->getPath();

        return $this->routeMiddlewares[$path] ?? [];
    }

    /**
     * @param ServerRequestInterface $request
     * @param mixed $handler
     * @param array<string, string> $routeParams
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    private function callHandler(ServerRequestInterface $request, mixed $handler, array $routeParams = []): mixed
    {
        if (is_callable($handler)) {
            return $handler($request);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;

            if (!is_string($controllerClass)) {
                throw new RuntimeException('Controller must be class-string');
            }

            $controller = $this->container->get($controllerClass);

            if (!method_exists($controller, $method)) {
                throw new RuntimeException("Method $method not found in $controllerClass");
            }

            return $controller->$method($request, ...array_values($routeParams));
        }

        throw new RuntimeException('Invalid route handler');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function ensureResponse(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (is_string($result)) {
            return $this->createHtmlResponse($result);
        }

        throw new RuntimeException('Handler must return string or ResponseInterface');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createHtmlResponse(string $html): ResponseInterface
    {
        $response = $this->psr17()->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function handleException(Throwable $e): ResponseInterface
    {

        $this->container->get(LoggerInterface::class)->error('Unhandled exception', [
            'exception' => $e,
        ]);

        return $this->createErrorResponse(500, 'Internal Server Error');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createErrorResponse(
        int    $statusCode,
        string $message,
    ): ResponseInterface
    {
        $errorMessages = [
            404 => [
                'title' => '404 - Page Not Found',
                'message' => 'The page you are looking for might have been removed, or is temporarily unavailable.',
            ],
            405 => [
                'title' => '405 - Method Not Allowed',
                'message' => 'The requested method is not allowed for this resource.',
            ],
            500 => [
                'title' => '500 - Internal Server Error',
                'message' => 'Something went wrong on our server.',
            ]
        ];

        $errorConfig = $errorMessages[$statusCode] ?? [
            'title' => "$statusCode - Error",
            'message' => $message,
        ];

        $errorViewModel = new ErrorViewModel(
            title: $errorConfig['title'],
            errorCode: (string)$statusCode,
            message: $errorConfig['message'],
        );

        $html = View::display($errorViewModel);

        return $this->psr17()->createResponse($statusCode)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->psr17()->createStream($html));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function psr17(): Psr17Factory
    {
        return $this->container->get(Psr17Factory::class);
    }
}
