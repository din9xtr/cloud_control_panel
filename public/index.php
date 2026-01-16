<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Din9xtrCloud\App;
use Din9xtrCloud\Container\Container;
use Din9xtrCloud\Controllers\AuthController;
use Din9xtrCloud\Controllers\DashboardController;
use Din9xtrCloud\Controllers\LicenseController;
use Din9xtrCloud\Controllers\StorageController;
use Din9xtrCloud\Controllers\StorageTusController;
use Din9xtrCloud\Middlewares\AuthMiddleware;
use Din9xtrCloud\Middlewares\CsrfMiddleware;
use Din9xtrCloud\Middlewares\ThrottleMiddleware;
use Din9xtrCloud\Router;
use Din9xtrCloud\Storage\Drivers\LocalStorageDriver;
use Din9xtrCloud\Storage\Drivers\StorageDriverInterface;
use Din9xtrCloud\Storage\UserStorageInitializer;
use FastRoute\RouteCollector;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Nyholm\Psr7\Factory\Psr17Factory;
use Monolog\Level;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

// ---------------------------------------------------------------------
// ENV
// ---------------------------------------------------------------------
$storageBasePath = dirname(__DIR__) . '/' . ($_ENV['STORAGE_PATH'] ?? 'storage');
$userLimitBytes = (int)($_ENV['STORAGE_USER_LIMIT_GB'] ?? 70) * 1024 * 1024 * 1024;
// ---------------------------------------------------------------------
// Container
// ---------------------------------------------------------------------
$container = new Container();

$logPath = dirname(__DIR__) . '/logs/cloud.log';
if (!is_dir(dirname($logPath))) mkdir(dirname($logPath), 0755, true);

$container->singleton(StorageDriverInterface::class, function () use ($storageBasePath, $userLimitBytes) {
    return new LocalStorageDriver(
        basePath: $storageBasePath,
        defaultLimitBytes: $userLimitBytes
    );
});
$container->singleton(UserStorageInitializer::class, function () use ($storageBasePath) {
    return new UserStorageInitializer($storageBasePath);
});
$container->singleton(LoggerInterface::class, function () use ($logPath) {
    $logger = new Logger('cloud');
    $logger->pushHandler(new StreamHandler($logPath, Level::Debug));
    $logger->pushProcessor(new PsrLogMessageProcessor());
    return $logger;
});
$container->singleton(PDO::class, function () {
    return new PDO(
        'sqlite:/var/db/database.sqlite',
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
});
$container->singleton(Psr17Factory::class, fn() => new Psr17Factory());

$container->request(
    ServerRequestInterface::class,
    fn(Container $c) => new ServerRequestCreator(
        $c->get(Psr17Factory::class),
        $c->get(Psr17Factory::class),
        $c->get(Psr17Factory::class),
        $c->get(Psr17Factory::class),
    )->fromGlobals()
);

// ---------------------------------------------------------------------
// Routes
// ---------------------------------------------------------------------
$routes = static function (RouteCollector $r): void {

    $r->get('/', [DashboardController::class, 'index']);
    $r->get('/license', [LicenseController::class, 'license']);

    $r->get('/login', [AuthController::class, 'loginForm']);
    $r->post('/login', [AuthController::class, 'loginSubmit']);
    $r->post('/logout', [AuthController::class, 'logout']);

    $r->post('/storage/folders', [StorageController::class, 'createFolder']);
    $r->post('/storage/files', [StorageController::class, 'uploadFile']);

    $r->get('/folders/{folder}', [StorageController::class, 'showFolder']);

    $r->post('/storage/folders/{folder}/delete', [StorageController::class, 'deleteFolder']);

    $r->addRoute(['POST', 'OPTIONS'], '/storage/tus', [
        StorageTusController::class,
        'handle',
    ]);
    $r->patch('/storage/tus/{id:[a-f0-9]+}', [StorageTusController::class, 'patch']);
    $r->head('/storage/tus/{id:[a-f0-9]+}', [StorageTusController::class, 'head']);

    $r->get('/storage/files/download', [StorageController::class, 'downloadFile']);
    $r->post('/storage/files/delete', [StorageController::class, 'deleteFile']);

    $r->get('/storage/files/download/multiple', [StorageController::class, 'downloadMultiple']);
    $r->post('/storage/files/delete/multiple', [StorageController::class, 'deleteMultiple']);
};


// route,middlewares
$router = new Router($routes, $container);
$router->middlewareFor('/login', ThrottleMiddleware::class);
//$router->middlewareFor('/login', AuthMiddleware::class);


$app = new App($container);

//global,middlewares
$app->middleware(
    CsrfMiddleware::class,
    AuthMiddleware::class
);
$app->router($router);

try {
    $container->beginRequest();
    $app->dispatch();
} catch (Throwable $e) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $container->get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Internal Server Error';
}