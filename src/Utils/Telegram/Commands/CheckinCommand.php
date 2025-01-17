<?php

namespace App\Utils\Telegram\Commands;

use App\Models\User;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

/**
 * Class CheckinCommand.
 */
class CheckinCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = 'checkin';

    /**
     * @var string Command Description
     */
    protected string $description = '[群组/私聊] 每日签到.';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $Update = $this->getUpdate();
        $Message = $Update->getMessage();

        // 消息会话 ID
        $ChatID = $Message->getChat()->getId();

        if ($ChatID < 0) {
            if ($_ENV['telegram_group_quiet'] === true) {
                // 群组中不回应
                return null;
            }
            if ($ChatID != $_ENV['telegram_chatid']) {
                // 非我方群组
                return null;
            }
        }

        // 发送 '输入中' 会话状态
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        // 触发用户
        $SendUser = [
            'id'       => $Message->getFrom()->getId(),
            'name'     => $Message->getFrom()->getFirstName() . ' ' . $Message->getFrom()->getLastName(),
            'username' => $Message->getFrom()->getUsername(),
        ];

        $User = User::where('telegram_id', $SendUser['id'])->first();
        if ($User == null) {
            // 回送信息
            $response = $this->replyWithMessage(
                [
                    'text'       => $_ENV['user_not_bind_reply'],
                    'parse_mode' => 'Markdown',
                ]
            );
        } else {
            $checkin = $User->checkin();
            // 回送信息
            $response = $this->replyWithMessage(
                [
                    'text'                  => $checkin['msg'],
                    'reply_to_message_id'   => $Message->getMessageId(),
                    'parse_mode'            => 'Markdown',
                ]
            );
        }
        return $response;
    }
}
