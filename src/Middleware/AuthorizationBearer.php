<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;

class AuthorizationBearer implements MiddlewareInterface
{
    protected string $token;

    function __construct(string $token) {
        $this->token = $token;
    }

    public
    function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->hasHeader('Authorization')) {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'ret' => 0,
                'data' => 'Authorization failed',
            ]);
        }

        $authHeader = $request->getHeaderLine('Authorization');

        // Bearer method token verify
        if (strtoupper(substr($authHeader, 0, 6)) != 'BEARER') {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'ret' => 0,
                'data' => 'Authorization failed',
            ]);
        }

        $realToken = substr($authHeader, 7);

        if ($realToken != $this->token) {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'ret' => 0,
                'data' => 'Authorization failed',
            ]);
        }

        return $handler->handle($request);
    }
}
