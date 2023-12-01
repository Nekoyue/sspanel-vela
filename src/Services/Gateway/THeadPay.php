<?php

namespace App\Services\Gateway;

use App\Services\Auth;
use App\Services\View;
use App\Models\Paylist;
use App\Models\Setting;
use Exception;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class THeadPay extends AbstractPayment
{
    public static function _name(): string
    {
        return 'theadpay';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('theadpay');
    }

    public static function _readableName(): string
    {
        return "THeadPay 平头哥支付";
    }

    protected THeadPaySDK $sdk;

    public function __construct()
    {
        $configs = Setting::getClass('theadpay');

        $this->sdk = new THeadPaySDK([
            'theadpay_url'      => $configs['theadpay_url'],
            'theadpay_mchid'    => $configs['theadpay_mchid'],
            'theadpay_key'      => $configs['theadpay_key'],
        ]);
    }


    public function purchase(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $amount = (int)$request->getParam('amount');
        $user = Auth::getUser();
        if ($amount <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '订单金额错误：' . $amount
            ]);
        }

        $pl = new Paylist();
        $pl->userid     = $user->id;
        $pl->tradeno    = self::generateGuid();
        $pl->total      = $amount;
        $pl->save();

        try {
            $res = $this->sdk->pay([
                'trade_no'      => $pl->tradeno,
                'total_fee'     => $pl->total*100,
                'notify_url'    => self::getCallbackUrl(),
                'return_url'    => self::getUserReturnUrl(),
            ]);

            return $response->withJson([
                'ret'       => 1,
                'qrcode'    => $res['code_url'],
                'amount'    => $pl->total,
                'pid'       => $pl->tradeno,
            ]);
        } catch (Exception $e) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '创建支付订单错误：' . $e->getMessage(),
            ]);
        }
    }

    public function notify(ServerRequest $request, Response $response, array $args): void
    {
        $inputString = file_get_contents('php://input', 'r');
        $inputStripped = str_replace(array("\r", "\n", "\t", "\v"), '', $inputString);
        $params = json_decode($inputStripped, true); //convert JSON into array

        if ($this->sdk->verify($params)) {
            $pid = $params['out_trade_no'];
            $this->postPayment($pid, 'THeadPay 平头哥支付 ' . $pid);
            die('success'); //The response should be 'success' only
        }

        die('fail');
    }


    /**
     * @throws \SmartyException
     */
    public static function getPurchaseHTML(): bool|string
    {
        return View::getSmarty()->fetch('user/theadpay.tpl');
    }

    public function getReturnHTML(ServerRequest $request, Response $response, array $args): int
    {
        return 0;
    }

    public function getStatus(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $pid = $request->getParam('pid');

        $p = Paylist::where('tradeno', $pid)->first();
        return $response->withJson([
            'ret'       => 1,
            'result'    => $p->status,
        ]);
    }
}
