<?php
/**
 * Created by PhpStorm.
 * User: tonyzou
 * Date: 2018/9/27
 * Time: 7:20 PM
 */

namespace App\Services\Gateway;

use App\Models\User;
use App\Models\Code;
use App\Models\Setting;
use App\Models\Payback;
use App\Utils\Telegram;
use App\Services\Auth;
use Paymentwall_Config;
use Paymentwall_Pingback;
use Paymentwall_Widget;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;

class PaymentWall extends AbstractPayment
{
    public static function _name(): string
    {
        return 'paymentwall';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('paymentwall');
    }

    public function purchase(ServerRequest $request, Response $response, array $args)
    {
        // TODO: Implement purchase() method.
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws TelegramSDKException
     */
    public function notify(ServerRequest $request, Response $response, array $args): void
    {
        $configs = Setting::getClass('pmw');
        if ($configs['pmw_publickey'] != '') {
            Paymentwall_Config::getInstance()->set(array(
                'api_type' => Paymentwall_Config::API_VC,
                'public_key' => $configs['pmw_publickey'],
                'private_key' => $configs['pmw_privatekey']
            ));
            $pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);
            if ($pingback->validate()) {
                $virtualCurrency = $pingback->getVirtualCurrencyAmount();
                if ($pingback->isDeliverable()) {
                    // deliver the virtual currency
                } elseif ($pingback->isCancelable()) {
                    // withdraw the virual currency
                }
                $user = User::find($pingback->getUserId());
                $user->money += $pingback->getVirtualCurrencyAmount();
                $user->save();
                $codeq = new Code();
                $codeq->code = 'Payment Wall 充值';
                $codeq->isused = 1;
                $codeq->type = -1;
                $codeq->number = $pingback->getVirtualCurrencyAmount();
                $codeq->usedatetime = date('Y-m-d H:i:s');
                $codeq->userid = $user->id;
                $codeq->save();
                // 返利
                if ($user->ref_by > 0 && Setting::obtain('invitation_mode') == 'after_recharge') {
                    Payback::rebate($user->id, $virtualCurrency);
                }
                // 通知
                echo 'OK'; // Paymentwall expects response to be OK, otherwise the pingback will be resent
                if ($_ENV['enable_donate'] == true) {
                    if ($user->is_hide == 1) {
                        Telegram::Send('姐姐姐姐，一位不愿透露姓名的大老爷给我们捐了 ' . $codeq->number . ' 元呢~');
                    } else {
                        Telegram::Send('姐姐姐姐，' . $user->user_name . ' 大老爷给我们捐了 ' . $codeq->number . ' 元呢~');
                    }
                }
            } else {
                echo $pingback->getErrorSummary();
            }
        } else {
            echo 'error';
        }
    }


    public static function getPurchaseHTML(): string
    {
        $configs = Setting::getClass('pmw');
        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_VC,
            'public_key' => $configs['pmw_publickey'],
            'private_key' => $configs['pmw_privatekey']
        ));
        $user = Auth::getUser();
        $widget = new Paymentwall_Widget(
            $user->id, // id of the end-user who's making the payment
            $configs['pmw_widget'],      // widget code, e.g. p1; can be picked inside of your merchant account
            array(),     // array of products - leave blank for Virtual Currency API
            array(
                'email' => $user->email,
                'history' =>
                    array(
                        'registration_date' => strtotime($user->reg_date),
                        'registration_ip' => $user->reg_ip,
                        'payments_number' => Code::where('userid', '=', $user->id)->where('type', '=', -1)->count(),
                        'membership' => $user->class),
                'customer' => array(
                    'username' => $user->user_name
                )
            ) // additional parameters
        );
        return $widget->getHtmlCode(array('height' => $configs['pmw_height'], 'width' => '100%'));
    }

    public function getReturnHTML(ServerRequest $request, Response $response, array $args)
    {
        // TODO: Implement getReturnHTML() method.
    }

    public function getStatus(ServerRequest $request, Response $response, array $args)
    {
        // TODO: Implement getStatus() method.
    }
}
