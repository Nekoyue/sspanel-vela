<?php

namespace App\Command;

use App\Models\User;
use App\Utils\Telegram;
use App\Utils\DatatablesHelper;
use Ozdemir\Datatables\Datatables;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;

class FinanceMail extends Command
{
    public string $description = ''
        . '├─=: php xcat FinanceMail [选项]' . PHP_EOL
        . '│ ├─ day                     - 日报' . PHP_EOL
        . '│ ├─ week                    - 周报' . PHP_EOL
        . '│ ├─ month                   - 月报' . PHP_EOL;

    public function boot(): void
    {
        if (count($this->argv) === 2) {
            echo $this->description;
        } else {
            $methodName = $this->argv[2];
            if (method_exists($this, $methodName)) {
                $this->$methodName();
            } else {
                echo '方法不存在.' . PHP_EOL;
            }
        }
    }

    /**
     * @throws TelegramSDKException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function day(): void
    {
        $datatables = new Datatables(new DatatablesHelper());
        $datatables->query(
            'select code.number, code.userid, code.usedatetime from code
		where TO_DAYS(NOW()) - TO_DAYS(code.usedatetime) = 1 and code.type = -1 and code.isused= 1'
        );
        $text_json = $datatables->generate();
        $text_array = json_decode($text_json, true);
        $codes = $text_array['data'];
        $text_html = '<table border=1><tr><td>金额</td><td>用户ID</td><td>用户名</td><td>充值时间</td>';
        $income_count = 0;
        $income_total = 0.00;
        foreach ($codes as $code) {
            $text_html .= '<tr>';
            $text_html .= '<td>' . $code['number'] . '</td>';
            $text_html .= '<td>' . $code['userid'] . '</td>';
            $user = User::find($code['userid']);
            $text_html .= '<td>' . $user->user_name . '</td>';
            $text_html .= '<td>' . $code['usedatetime'] . '</td>';
            $text_html .= '</tr>';
            ++$income_count;
            $income_total += $code['number'];
        }

        $text_html .= '</table>';
        $text_html .= '<br>昨日总收入笔数：' . $income_count . '<br>昨日总收入金额：' . $income_total;

        $adminUser = User::where('is_admin', '=', '1')->get();
        foreach ($adminUser as $user) {
            echo 'Send offline mail to user: ' . $user->id;
            $user->sendMail(
                $_ENV['appName'] . '-财务日报',
                'news/finance.tpl',
                [
                    'title' => '财务日报',
                    'text'  => $text_html
                ],
                []
            );
        }

        if ($_ENV['finance_public']) {
            Telegram::Send(
                '新鲜出炉的财务日报~' . PHP_EOL .
                '昨日总收入笔数:' . $income_count . PHP_EOL .
                '昨日总收入金额:' . $income_total . PHP_EOL .
                '凌晨也在努力工作~'
            );
        }
    }

    /**
     * @throws TelegramSDKException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function week(): void
    {
        $datatables = new Datatables(new DatatablesHelper());
        $datatables->query(
            'SELECT code.number FROM code
		WHERE DATEDIFF(NOW(),code.usedatetime) <=7 AND DATEDIFF(NOW(),code.usedatetime) >=1 AND code.isused = 1'
        );
        //每周的第一天是周日，因此统计周日～周六的七天
        $text_json = $datatables->generate();
        $text_array = json_decode($text_json, true);
        $codes = $text_array['data'];
        $text_html = '';
        $income_count = 0;
        $income_total = 0.00;
        foreach ($codes as $code) {
            ++$income_count;
            $income_total += $code['number'];
        }

        $text_html .= '<br>上周总收入笔数：' . $income_count . '<br>上周总收入金额：' . $income_total;

        $adminUser = User::where('is_admin', '=', '1')->get();
        foreach ($adminUser as $user) {
            echo 'Send offline mail to user: ' . $user->id;
            $user->sendMail(
                $_ENV['appName'] . '-财务周报',
                'news/finance.tpl',
                [
                    'title' => '财务周报',
                    'text'  => $text_html
                ],
                []
            );
        }

        if ($_ENV['finance_public']) {
            Telegram::Send(
                '新鲜出炉的财务周报~' . PHP_EOL .
                '上周总收入笔数:' . $income_count . PHP_EOL .
                '上周总收入金额:' . $income_total . PHP_EOL .
                '周末也在努力工作~'
            );
        }
    }

    /**
     * @throws TelegramSDKException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function month(): void
    {
        $datatables = new Datatables(new DatatablesHelper());
        $datatables->query(
            'select code.number from code
		where date_format(code.usedatetime,\'%Y-%m\')=date_format(date_sub(curdate(), interval 1 month),\'%Y-%m\') and code.type = -1 and code.isused= 1'
        );
        $text_json = $datatables->generate();
        $text_array = json_decode($text_json, true);
        $codes = $text_array['data'];
        $text_html = '';
        $income_count = 0;
        $income_total = 0.00;
        foreach ($codes as $code) {
            ++$income_count;
            $income_total += $code['number'];
        }
        $text_html .= '<br>上月总收入笔数：' . $income_count . '<br>上月总收入金额：' . $income_total;

        $adminUser = User::where('is_admin', '=', '1')->get();
        foreach ($adminUser as $user) {
            echo 'Send offline mail to user: ' . $user->id;
            $user->sendMail(
                $_ENV['appName'] . '-财务月报',
                'news/finance.tpl',
                [
                    'title' => '财务月报',
                    'text'  => $text_html
                ],
                []
            );
        }

        if ($_ENV['finance_public']) {
            Telegram::Send(
                '新鲜出炉的财务月报~' . PHP_EOL .
                '上月总收入笔数:' . $income_count . PHP_EOL .
                '上月总收入金额:' . $income_total . PHP_EOL .
                '月初也在努力工作~'
            );
        }
    }
}
