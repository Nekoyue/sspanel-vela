<?php

namespace App\Models;

use App\Controllers\LinkController;
use App\Services\{Config, Mail};
use App\Utils\{GA, Hash, Telegram, Tools, URL};
use Exception;
use Ramsey\Uuid\Uuid;

/**
 * User Model
 *
 * @property-read   int     $id         ID
 * @todo More property
 * @property        bool    $is_admin           是否管理员
 * @property        bool    $expire_notified    If user is notified for expire
 * @property        bool    $traffic_notified   If user is noticed for low traffic
 * @property string $user_name
 * @property string $email
 * @property string $pass
 * @property string $passwd
 * @property string $uuid
 * @property int $port
 * @property int $ga_enable
 * @property string $ga_token
 * @property string $theme
 * @property int $node_speedlimit
 * @property int $class
 * @property string $im_value
 * @property int $im_type
 * @property int $money
 * @property string $reg_date
 * @property string $expire_in
 * @property int $ref_by
 * @property int $invite_num
 * @property float|int $transfer_enable
 * @property int $d
 * @property int $u
 * @property int $t
 */
class User extends Model
{
    protected $connection = 'default';

    protected $table = 'user';

    /**
     * 已登录
     *
     * @var bool
     */
    public bool $isLogin;

    /**
     * 强制类型转换
     *
     * @var array
     */
    protected $casts = [
        't'               => 'float',
        'u'               => 'float',
        'd'               => 'float',
        'port'            => 'int',
        'transfer_enable' => 'float',
        'enable'          => 'int',
        'is_admin'        => 'boolean',
        'is_multi_user'   => 'int',
        'node_speedlimit' => 'float',
        'sendDailyMail'   => 'int',
        'ref_by'          => 'int'
    ];

    /**
     * Gravatar 头像地址
     */
    public function getGravatarAttribute(): string
    {
        $hash = md5(strtolower(trim($this->email)));
        return 'https://www.gravatar.com/avatar/' . $hash . '?&d=identicon';
    }

    /**
     * 联系方式类型
     */
    public function im_type(): string
    {
        return match ($this->im_type) {
            1 => '微信',
            2 => 'QQ',
            5 => 'Discord',
            default => 'Telegram',
        };
    }

    /**
     * 联系方式
     */
    public function im_value(): string
    {
        return match ($this->im_type) {
            1, 2, 5 => $this->im_value,
            default => '<a href="https://telegram.me/' . $this->im_value . '">' . $this->im_value . '</a>',
        };
    }


    /**
     * 最后使用时间
     */
    public function lastSsTime(): string
    {
        return $this->t == 0 ? '从未使用喵' : Tools::toDateTime($this->t);
    }

    /**
     * 最后签到时间
     */
    public function lastCheckInTime(): string
    {
        return $this->last_check_in_time == 0 ? '从未签到' : Tools::toDateTime($this->last_check_in_time);
    }

    /**
     * 更新密码
     *
     * @param string $pwd
     * @return bool
     */
    public function updatePassword(string $pwd): bool
    {
        $this->pass = Hash::passwordHash($pwd);
        return $this->save();
    }

    public function get_forbidden_ip()
    {
        return str_replace(',', PHP_EOL, $this->forbidden_ip);
    }

    public function get_forbidden_port()
    {
        return str_replace(',', PHP_EOL, $this->forbidden_port);
    }

    /**
     * 更新连接密码
     *
     * @param string $pwd
     * @return bool
     */
    public function updateSsPwd(string $pwd): bool
    {
        $this->passwd = $pwd;
        return $this->save();
    }

    /**
     * 更新加密方式
     *
     * @param string $method
     * @return array
     */
    public function updateMethod(string $method): array
    {
        $return = [
            'ok' => true,
            'msg' => "updateMethod 未启用"
        ];

        $this->method = $method;
        $this->save();

        return $return;
    }

    /**
     * 生成邀请码
     */
    public function addInviteCode(): string
    {
        while (true) {
            $temp_code = Tools::genRandomChar(10);
            if (InviteCode::where('code', $temp_code)->first() == null) {
                if (InviteCode::where('user_id', $this->id)->count() == 0) {
                    $code          = new InviteCode();
                    $code->code    = $temp_code;
                    $code->user_id = $this->id;
                    $code->save();
                    return $temp_code;
                } else {
                    return (InviteCode::where('user_id', $this->id)->first())->code;
                }
            }
        }
    }

    /**
     * 添加邀请次数
     */
    public function addInviteNum(int $num): bool
    {
        $this->invite_num += $num;
        return $this->save();
    }

    /**
     * 生成新的UUID
     */
    public function generateUUID($s): bool
    {
        $this->uuid = Uuid::uuid3(
            Uuid::NAMESPACE_DNS,
            $this->email . '|' . $s
        );
        return $this->save();
    }

    /*
     * 总流量[自动单位]
     */
    public function enableTraffic(): string
    {
        return Tools::flowAutoShow($this->transfer_enable);
    }

    /*
     * 总流量[GB]，不含单位
     */
    public function enableTrafficInGB(): float
    {
        return Tools::flowToGB($this->transfer_enable);
    }

    /*
     * 已用流量[自动单位]
     */
    public function usedTraffic(): string
    {
        return Tools::flowAutoShow($this->u + $this->d);
    }

    /*
     * 已用流量占总流量的百分比
     */
    public function trafficUsagePercent(): int
    {
        if ($this->transfer_enable == 0) {
            return 0;
        }
        $percent  = ($this->u + $this->d) / $this->transfer_enable;
        $percent  = round($percent, 2);
        $percent *= 100;
        return $percent;
    }

    /*
     * 剩余流量[自动单位]
     */
    public function unusedTraffic(): string
    {
        return Tools::flowAutoShow($this->transfer_enable - ($this->u + $this->d));
    }

    /*
     * 剩余流量占总流量的百分比
     */
    public function unusedTrafficPercent(): int
    {
        if ($this->transfer_enable == 0) {
            return 0;
        }
        $unused   = $this->transfer_enable - ($this->u + $this->d);
        $percent  = $unused / $this->transfer_enable;
        $percent  = round($percent, 2);
        $percent *= 100;
        return $percent;
    }

    /*
     * 今天使用的流量[自动单位]
     */
    public function TodayusedTraffic(): string
    {
        return Tools::flowAutoShow($this->u + $this->d - $this->last_day_t);
    }

    /*
     * 今天使用的流量占总流量的百分比
     */
    public function TodayusedTrafficPercent(): int
    {
        if ($this->transfer_enable == 0) {
            return 0;
        }
        $Todayused = $this->u + $this->d - $this->last_day_t;
        $percent   = $Todayused / $this->transfer_enable;
        $percent   = round($percent, 2);
        $percent  *= 100;
        return $percent;
    }

    /*
     * 今天之前已使用的流量[自动单位]
     */
    public function LastusedTraffic(): string
    {
        return Tools::flowAutoShow($this->last_day_t);
    }

    /*
     * 今天之前已使用的流量占总流量的百分比
     */
    public function LastusedTrafficPercent(): int
    {
        if ($this->transfer_enable == 0) {
            return 0;
        }
        $Lastused = $this->last_day_t;
        $percent  = $Lastused / $this->transfer_enable;
        $percent  = round($percent, 2);
        $percent *= 100;
        return $percent;
    }

    /*
     * 是否可以签到
     */
    public function isAbleToCheckin(): bool
    {
        return date('Ymd') != date('Ymd', $this->last_check_in_time);
    }

    public function getGAurl(): string
    {
        $ga = new GA();
        $url = $ga->getUrl(
            urlencode($_ENV['appName'] . '-' . $this->user_name . '-两步验证码'),
            $this->ga_token
        );
        return $url;
    }

    /**
     * 获取用户的邀请码
     */
    public function getInviteCodes(): ?InviteCode
    {
        return InviteCode::where('user_id', $this->id)->first();
    }

    /**
     * 用户的邀请人
     */
    public function ref_by_user(): ?User
    {
        return self::find($this->ref_by);
    }

    /**
     * 用户邀请人的用户名
     */
    public function ref_by_user_name(): string
    {
        if ($this->ref_by == 0) {
            return '系统邀请';
        } else {
            if ($this->ref_by_user() == null) {
                return '邀请人已经被删除';
            } else {
                return $this->ref_by_user()->user_name;
            }
        }
    }

    /**
     * 删除用户的订阅链接
     */
    public function clean_link(): void
    {
        Link::where('userid', $this->id)->delete();
    }

    /**
     * 获取用户的订阅链接
     */
    public function getSublink(): string
    {
        return LinkController::GenerateSSRSubCode($this->id);
    }

    /**
     * 删除用户的邀请码
     */
    public function clear_inviteCodes(): void
    {
        InviteCode::where('user_id', $this->id)->delete();
    }

    /**
     * 在线 IP 个数
     */
    public function online_ip_count(): int
    {
        // 根据 IP 分组去重
        $total = Ip::where('datetime', '>=', time() - 90)->where('userid', $this->id)->orderBy('userid', 'desc')->groupBy('ip')->get();
        $ip_list = [];
        foreach ($total as $single_record) {
            $ip = Tools::getRealIp($single_record->ip);
            if (Node::where('node_ip', $ip)->first() != null) {
                continue;
            }
            $ip_list[] = $ip;
        }
        return count($ip_list);
    }

    /**
     * 销户
     */
    public function kill_user(): bool
    {
        $uid   = $this->id;
        $email = $this->email;

        Bought::where('userid', '=', $uid)->delete();
        Code::where('userid', '=', $uid)->delete();
        DetectBanLog::where('user_id', '=', $uid)->delete();
        DetectLog::where('user_id', '=', $uid)->delete();
        EmailVerify::where('email', $email)->delete();
        InviteCode::where('user_id', '=', $uid)->delete();
        Ip::where('userid', '=', $uid)->delete();
        Link::where('userid', '=', $uid)->delete();
        LoginIp::where('userid', '=', $uid)->delete();
        PasswordReset::where('email', '=', $email)->delete();
        TelegramSession::where('user_id', '=', $uid)->delete();
        Token::where('user_id', '=', $uid)->delete();
        UnblockIp::where('userid', '=', $uid)->delete();
        UserSubscribeLog::where('user_id', '=', $uid)->delete();

        $this->delete();

        return true;
    }

    /**
     * 累计充值金额
     */
    public function get_top_up(): float
    {
        $number = Code::where('userid', $this->id)->sum('number');
        return is_null($number) ? 0.00 : round($number, 2);
    }

    /**
     * 获取累计收入
     *
     * @param string $req
     * @return float
     */
    public function calIncome(string $req): float
    {
        $number = match ($req) {
            "yesterday" => Code::whereDate('usedatetime', '=', date('Y-m-d', strtotime('-1 days')))->sum('number'),
            "today" => Code::whereDate('usedatetime', '=', date('Y-m-d'))->sum('number'),
            "this month" => Code::whereYear('usedatetime', '=', date('Y'))->whereMonth('usedatetime', '=', date('m'))->sum('number'),
            "last month" => Code::whereYear('usedatetime', '=', date('Y'))->whereMonth('usedatetime', '=', date('m', strtotime('last month')))->sum('number'),
            default => Code::sum('number'),
        };
        return is_null($number) ? 0.00 : round($number, 2);
    }

    /**
     * 获取付费用户总数
     */
    public function paidUserCount(): int
    {
        return self::where('class', '!=', '0')->count();
    }

    /**
     * 获取用户被封禁的理由
     */
    public function disableReason(): string
    {
        $reason_id = DetectLog::where('user_id', $this->id)->orderBy('id', 'DESC')->first();
        $reason    = DetectRule::find($reason_id->list_id);
        if (is_null($reason)) {
            return '特殊原因被禁用，了解详情请联系管理员';
        }
        return $reason->text;
    }

    /**
     * 最后一次被封禁的时间
     */
    public function last_detect_ban_time(): string
    {
        return ($this->last_detect_ban_time == '1989-06-04 00:05:00' ? '未被封禁过' : $this->last_detect_ban_time);
    }

    /**
     * 当前解封时间
     */
    public function relieve_time(): string
    {
        $logs = DetectBanLog::where('user_id', $this->id)->orderBy('id', 'desc')->first();
        if ($this->enable == 0 && $logs != null) {
            $time = ($logs->end_time + $logs->ban_time * 60);
            return date('Y-m-d H:i:s', $time);
        } else {
            return '当前未被封禁';
        }
    }

    /**
     * 累计被封禁的次数
     */
    public function detect_ban_number(): int
    {
        return DetectBanLog::where('user_id', $this->id)->count();
    }

    /**
     * 最后一次封禁的违规次数
     */
    public function user_detect_ban_number(): int
    {
        $logs = DetectBanLog::where('user_id', $this->id)->orderBy('id', 'desc')->first();
        return $logs->detect_number;
    }

    /**
     * 签到
     * @throws \Random\RandomException
     */
    public function checkin(): array
    {
        $return = [];
        if ($this->isAbleToCheckin()) {
            $traffic = random_int((int)$_ENV['checkinMin'], (int)$_ENV['checkinMax']);
            $this->transfer_enable += Tools::toMB($traffic);
            $this->last_check_in_time = time();
            $this->save();
            $return['ok'] = true;
            $return['msg'] = '获得了 ' . $traffic . 'MB 流量.';
        } else {
            $return['ok'] = false;
            $return['msg'] = '您似乎已经签到过了...';
        }

        return $return;
    }


    /**
     * 解绑 Telegram
     */
    public function TelegramReset(): array
    {
        $return = [
            'ok'  => true,
            'msg' => '解绑成功.'
        ];
        $telegram_id = $this->telegram_id;
        $this->telegram_id = 0;
        if ($this->save()) {
            if (
                $_ENV['enable_telegram'] === true
                &&
                Config::getconfig('Telegram.bool.group_bound_user') === true
                &&
                Config::getconfig('Telegram.bool.unbind_kick_member') === true
                &&
                !$this->is_admin
            ) {
                \App\Utils\Telegram\TelegramTools::SendPost(
                    'kickChatMember',
                    [
                        'chat_id'   => $_ENV['telegram_chatid'],
                        'user_id'   => $telegram_id,
                    ]
                );
            }
        } else {
            $return = [
                'ok'  => false,
                'msg' => '解绑失败.'
            ];
        }

        return $return;
    }

    /**
     * 更新端口
     *
     * @param int $Port
     */
    public function setPort(int $Port): array
    {
        $PortOccupied = User::pluck('port')->toArray();
        if (in_array($Port, $PortOccupied)) {
            return [
                'ok'  => false,
                'msg' => '端口已被占用'
            ];
        }
        $this->port = $Port;
        $this->save();
        return [
            'ok'  => true,
            'msg' => $this->port
        ];
    }

    /**
     * 重置端口
     */
    public function ResetPort(): array
    {
        $price = $_ENV['port_price'];
        if ($this->money < $price) {
            return [
                'ok'  => false,
                'msg' => '余额不足'
            ];
        }
        $this->money -= $price;
        $Port = Tools::getAvPort();
        $this->setPort($Port);
        $this->save();
        return [
            'ok'  => true,
            'msg' => $this->port
        ];
    }

    /**
     * 指定端口
     *
     * @param int $Port
     */
    public function SpecifyPort(int $Port): array
    {
        $price = $_ENV['port_price_specify'];
        if ($this->money < $price) {
            return [
                'ok'  => false,
                'msg' => '余额不足'
            ];
        }
        if ($Port < $_ENV['min_port'] || $Port > $_ENV['max_port'] || !Tools::isInt($Port)) {
            return [
                'ok'  => false,
                'msg' => '端口不在要求范围内'
            ];
        }
        $PortOccupied = User::pluck('port')->toArray();
        if (in_array($Port, $PortOccupied)) {
            return [
                'ok'  => false,
                'msg' => '端口已被占用'
            ];
        }
        $this->money -= $price;
        $this->setPort($Port);
        $this->save();
        return [
            'ok'  => true,
            'msg' => '钦定成功'
        ];
    }

    /**
     * 用户下次流量重置时间
     */
    public function valid_use_loop(): string
    {
        $boughts = Bought::where('userid', $this->id)->orderBy('id', 'desc')->get();
        $data = [];
        foreach ($boughts as $bought) {
            $shop = $bought->shop();
            if ($shop != null && $bought->valid()) {
                $data[] = $bought->reset_time();
            }
        }
        if (count($data) == 0) {
            return '以等级到期时间为准';
        }
        if (count($data) == 1) {
            return $data[0];
        }
        return '多个有效套餐无法显示';
    }

    /**
     * 手动修改用户余额时增加充值记录，受限于 Config
     *
     * @param mixed $total 金额
     */
    public function addMoneyLog(mixed $total): void
    {
        if ($_ENV['money_from_admin'] && $total != 0) {
            $codeq              = new Code();
            $codeq->code        = ($total > 0 ? '管理员赏赐' : '管理员惩戒');
            $codeq->isused      = 1;
            $codeq->type        = -1;
            $codeq->number      = $total;
            $codeq->usedatetime = date('Y-m-d H:i:s');
            $codeq->userid      = $this->id;
            $codeq->save();
        }
    }

    /**
     * 发送邮件
     *
     * @param string $subject
     * @param string $template
     * @param array $ary
     * @param array $files
     * @param bool $is_queue
     * @return bool
     */
    public function sendMail(string $subject, string $template, array $ary = [], array $files = [], bool $is_queue = false): bool
    {
        $result = false;
        if ($is_queue) {
            $new_emailqueue = new EmailQueue;
            $new_emailqueue->to_email = $this->email;
            $new_emailqueue->subject = $subject;
            $new_emailqueue->template = $template;
            $new_emailqueue->time = time();
            $ary = array_merge(['user' => $this], $ary);
            $new_emailqueue->array = json_encode($ary);
            $new_emailqueue->save();
            return true;
        }
        // 验证邮箱地址是否正确
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            // 发送邮件
            try {
                Mail::send(
                    $this->email,
                    $subject,
                    $template,
                    array_merge(
                        [
                            'user' => $this
                        ],
                        $ary
                    ),
                    $files
                );
                $result = true;
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        return $result;
    }

    /**
     * 发送 Telegram 讯息
     *
     * @param string $text
     * @return bool
     */
    public function sendTelegram(string $text): bool
    {
        $result = false;
        if ($this->telegram_id > 0) {
            Telegram::Send(
                $text,
                $this->telegram_id
            );
            $result = true;
        }
        return $result;
    }

    /**
     * 发送每日流量报告
     *
     * @param string $ann 公告
     */
    public function sendDailyNotification(string $ann = ''): void
    {
        $lastday = (($this->u + $this->d) - $this->last_day_t) / 1024 / 1024;
        switch ($this->sendDailyMail) {
            case 0:
                return;
            case 1:
                echo 'Send daily mail to user: ' . $this->id;
                $this->sendMail(
                    $_ENV['appName'] . '-每日流量报告以及公告',
                    'news/daily-traffic-report.tpl',
                    [
                        'user'    => $this,
                        'text'    => '下面是系统中目前的公告:<br><br>' . $ann . '<br><br>晚安！',
                        'lastday' => $lastday
                    ]
                );
                break;
            case 2:
                echo 'Send daily Telegram message to user: ' . $this->id;
                $text  = date('Y-m-d') . ' 流量使用报告' . PHP_EOL . PHP_EOL;
                $text .= '流量总计：' . $this->enableTraffic() . PHP_EOL;
                $text .= '已用流量：' . $this->usedTraffic() . PHP_EOL;
                $text .= '剩余流量：' . $this->unusedTraffic() . PHP_EOL;
                $text .= '今日使用：' . $lastday . 'MB';
                $this->sendTelegram(
                    $text
                );
                break;
        }
    }

    /**
     * 记录登录 IP
     *
     * @param string $ip
     * @param int $type 登录失败为 1
     * @return bool
     */
    public function collectLoginIP(string $ip, int $type = 0): bool
    {
        $loginip           = new LoginIp();
        $loginip->ip       = $ip;
        $loginip->userid   = $this->id;
        $loginip->datetime = time();
        $loginip->type     = $type;

        return $loginip->save();
    }
}
