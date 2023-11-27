<?php

namespace App\Controllers;

use App\Utils\Telegram\Process;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class CallbackController extends BaseController
{
    /**
     * @throws TelegramSDKException
     * @throws GuzzleException
     */
    public function telegram(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $token = $request->getQueryParam('token');
        if ($token == $_ENV['telegram_request_token']) {
            if ($_ENV['use_new_telegram_bot']) {
                Process::index($request);
            }
//            else {
//                TelegramProcess::process(); // deprecated
//            }
            $result = '1';
        } else {
            $result = '0';
        }
        return $response->write($result);
    }

}
