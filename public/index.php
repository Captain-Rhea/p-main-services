<?php

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use App\Database\Connection;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$app = AppFactory::create();

Connection::initialize();

$app->add(function (Request $request, RequestHandler $handler): Response {
    $origin = $request->getHeaderLine('Origin');
    $allowedOrigins = ['http://localhost:4000'];

    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();

        if (in_array($origin, $allowedOrigins)) {
            return $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus(200);
        }

        return $response->withStatus(403);
    }

    $response = $handler->handle($request);

    if (in_array($origin, $allowedOrigins)) {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization');
    }

    return $response;
});

$routes = require __DIR__ . '/../src/Routes/index.php';
$routes($app);

$app->run();
