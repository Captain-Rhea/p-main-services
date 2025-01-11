<?php

namespace App\Middleware;

use App\Helpers\AuthAPIHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $res = AuthAPIHelper::get('/v1/auth/verify-token', ['token' => $token]);
            $statusCode = $res->getStatusCode();
            $body = json_decode($res->getBody()->getContents(), true);

            if ($statusCode != 200) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode($body));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus($statusCode);
            }

            if (isset($body)) {
                $user = $body['data'];
                $request = $request->withAttribute('user', $user);
            }
            $request = $request->withAttribute('token', $token);
            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->unauthorizedResponse($e->getMessage());
        }
    }

    private function unauthorizedResponse(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
