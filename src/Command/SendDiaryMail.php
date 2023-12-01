<?php

namespace App\Command;

use App\Models\User;
use App\Models\Ann;
use App\Services\Config;
use App\Utils\Telegram;
use App\Utils\Tools;
use App\Services\Analytics;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;

class SendDiaryMail extends Command
{
    public string $description = '├─=: php xcat SendDiaryMail  - 每日流量报告' . PHP_EOL;

    /**
     * @throws TelegramSDKException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function boot(): void
    {
        $users = User::all();
        $logs = Ann::orderBy('id', 'desc')->get();
        $text1 = '';

        foreach ($logs as $log) {
            if (!str_contains($log->content, 'Links')) {
                $text1 = $text1 . $log->content . '<br><br>';
            }
        }

        $lastday_total = 0;

        foreach ($users as $user) {
            $lastday_total += (($user->u + $user->d) - $user->last_day_t);
            $user->sendDailyNotification($text1);
        }

        $sts = new Analytics();

        if (Config::getconfig('Telegram.bool.Diary')) {
            Telegram::Send(
                str_replace(
                    array(
                        '%getTodayCheckinUser%',
                        '%lastday_total%'
                    ),
                    array(
                        $sts->getTodayCheckinUser(),
                        Tools::flowAutoShow($lastday_total)
                    ),
                    Config::getconfig('Telegram.string.Diary')
                )
            );
        }
    }
}
