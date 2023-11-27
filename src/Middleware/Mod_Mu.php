<?php

namespace App\Middleware;

use App\Models\Node;
use App\Services\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;

class Mod_Mu implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $request->getQueryParam('key');
        if ($key === null) {
            // 未提供 key
            return AppFactory::determineResponseFactory()->createResponse(401)
                ->withjson([
                'ret'  => 0,
                'data' => 'Your key is null.'
            ]);
        }

        if (!in_array($key, Config::getMuKey())) {
            // key 不存在
            return AppFactory::determineResponseFactory()->createResponse(401)
                ->withJson([
                'ret'  => 0,
                'data' => 'Token is invalid.'
            ]);
        }

        if ($_ENV['WebAPI'] === false) {
            // 主站不提供 WebAPI
            return AppFactory::determineResponseFactory()->createResponse(403)
                ->withJson([
                'ret'  => 0,
                'data' => 'WebAPI is disabled.'
            ]);
        }

        if ($_ENV['checkNodeIp'] === true) {
            if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
                $node = Node::where('node_ip', 'LIKE', $_SERVER['REMOTE_ADDR'] . '%')->first();
                if ($node === null) {
                    return AppFactory::determineResponseFactory()->createResponse(401)
                        ->withJson([
                        'ret'  => 0,
                        'data' => 'IP is invalid. Now, your IP address is ' . $_SERVER['REMOTE_ADDR']
                    ]);
                }
            }
        }

        return $handler->handle($request);
    }
}
