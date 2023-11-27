<?php

namespace App\Utils\Telegram;

use App\Utils\Telegram\Callbacks\Callback;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class Process
{
    /**
     * @throws TelegramSDKException
     * @throws GuzzleException
     */
    public static function index(RequestInterface $request): void
    {
        $bot = new Api($_ENV['telegram_token']);
        $bot->addCommands(
            [
                Commands\MyCommand::class,
                Commands\HelpCommand::class,
                Commands\InfoCommand::class,
                Commands\MenuCommand::class,
                Commands\PingCommand::class,
                Commands\StartCommand::class,
                Commands\UnbindCommand::class,
                Commands\CheckinCommand::class,
                Commands\SetuserCommand::class,
            ]
        );

        $bot->commandsHandler(true, $request);
        $bot->setConnectTimeOut(1);
        $update = $bot->getWebhookUpdate();

        if ($update->has('callback_query')) {
            new Callback($bot, $update->getCallbackQuery());
        }
        if ($update->has('message')) {
            new Message($bot, $update->getMessage());
        }

    }
}
