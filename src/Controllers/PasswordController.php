<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use App\Models\{
    User,
    PasswordReset
};
use App\Utils\Hash;
use App\Services\Password;
use Slim\Http\{
    ServerRequest,
    Response
};

/***
 * Class Password
 * @package App\Controllers
 * 密码重置
 */
class PasswordController extends BaseController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function reset(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        return $response->write(
            $this->view()->fetch('password/reset.tpl')
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function handleReset(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $email = strtolower($request->getParam('email'));
        $user  = User::where('email', $email)->first();
        if ($user == null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '此邮箱不存在'
            ]);
        }
        if (Password::sendResetEmail($email)) {
            $msg = '重置邮件已经发送,请检查邮箱.';
        } else {
            $msg = '邮件发送失败，请联系网站管理员。';
        }
        return $response->withJson([
            'ret' => 1,
            'msg' => $msg
        ]);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface|Response
     * @throws \SmartyException
     */
    public function token(ServerRequest $request, Response $response, array $args)
    {
        $token = PasswordReset::where('token', $args['token'])->where('expire_time', '>', time())->orderBy('id', 'desc')->first();
        if ($token == null) return $response->withStatus(302)->withHeader('Location', '/password/reset');
        return $response->write(
            $this->view()->fetch('password/token.tpl')
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function handleToken(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $tokenStr = $args['token'];
        $password = $request->getParam('password');
        $repasswd = $request->getParam('repasswd');

        if ($password != $repasswd) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '两次输入不符合'
            ]);
        }

        if (strlen($password) < 8) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '密码太短啦'
            ]);
        }

        // check token
        $token = PasswordReset::where('token', $tokenStr)->where('expire_time', '>', time())->orderBy('id', 'desc')->first();
        if ($token == null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '链接已经失效，请重新获取'
            ]);
        }
        /** @var PasswordReset $token */
        $user = $token->getUser();
        if ($user == null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '链接已经失效，请重新获取'
            ]);
        }

        // reset password
        $hashPassword    = Hash::passwordHash($password);
        $user->pass      = $hashPassword;
        $user->ga_enable = 0;

        if (!$user->save()) {
            $rs['ret'] = 0;
            $rs['msg'] = '重置失败，请重试';
        } else {
            $rs['ret'] = 1;
            $rs['msg'] = '重置成功';

            if ($_ENV['enable_forced_replacement'] == true) {
                $user->clean_link();
            }

            // 禁止链接多次使用
            $token->expire_time = time();
            $token->save();
        }

        return $response->withJson($rs);
    }
}
