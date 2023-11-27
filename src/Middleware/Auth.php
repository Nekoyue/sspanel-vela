<?php

namespace App\Middleware;

use App\Services\Auth as AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;

class Auth implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = AuthService::getUser();
        if (!$user->isLogin) {
            return AppFactory::determineResponseFactory()->createResponse(302)->withHeader('Location', '/auth/login');
        }
        $enablePages = array('/user/disable', '/user/backtoadmin', '/user/logout');
        if ($user->enable == 0 && !in_array($_SERVER['REQUEST_URI'], $enablePages)) {
            return AppFactory::determineResponseFactory()->createResponse(302)->withHeader('Location', '/user/disable');
        }
        return $handler->handle($request);
    }
}
