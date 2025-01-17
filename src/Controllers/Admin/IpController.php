<?php

namespace App\Controllers\Admin;

use App\Controllers\AdminController;
use App\Models\{
    BlockIp,
    Ip,
    LoginIp,
    UnblockIp
};
use Psr\Http\Message\ResponseInterface;
use App\Utils\{
    QQWry,
    Tools
};
use Slim\Http\{
    ServerRequest,
    Response
};

class IpController extends AdminController
{
    /**
     * 后台登录记录页面
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function index(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $table_config['total_column'] = array(
            'id'        => 'ID',
            'userid'    => '用户ID',
            'user_name' => '用户名',
            'ip'        => 'IP',
            'location'  => '归属地',
            'datetime'  => '时间',
            'type'      => '类型'
        );
        $table_config['default_show_column'] = array_keys($table_config['total_column']);
        $table_config['ajax_url'] = 'login/ajax';
        return $response->write(
            $this->view()
                ->assign('table_config', $table_config)
                ->fetch('admin/ip/login.tpl')
        );
    }

    /**
     * 后台登录记录页面 AJAX
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function ajax_login(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $query = LoginIp::getTableDataFromAdmin(
            $request,
            static function (&$order_field) {
                if (in_array($order_field, ['user_name'])) {
                    $order_field = 'userid';
                }
                if (in_array($order_field, ['location'])) {
                    $order_field = 'ip';
                }
            }
        );

        $data  = [];
        $QQWry = new QQWry();
        foreach ($query['datas'] as $value) {
            /** @var LoginIp $value */

            if ($value->user() == null) {
                LoginIp::user_is_null($value);
                continue;
            }
            $tempdata              = [];
            $tempdata['id']        = $value->id;
            $tempdata['userid']    = $value->userid;
            $tempdata['user_name'] = $value->user_name();
            $tempdata['ip']        = $value->ip;
            $tempdata['location']  = $value->location($QQWry);
            $tempdata['datetime']  = $value->datetime();
            $tempdata['type']      = $value->type();

            $data[] = $tempdata;
        }

        return $response->withJson([
            'draw'            => $request->getParam('draw'),
            'recordsTotal'    => LoginIp::count(),
            'recordsFiltered' => $query['count'],
            'data'            => $data,
        ]);
    }

    /**
     * 后台在线 IP 页面
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function alive(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $table_config['total_column'] = array(
            'id'        => 'ID',
            'userid'    => '用户ID',
            'user_name' => '用户名',
            'nodeid'    => '节点ID',
            'node_name' => '节点名',
            'ip'        => 'IP',
            'location'  => '归属地',
            'datetime'  => '时间',
            'is_node'   => '是否为中转连接'
        );
        $table_config['default_show_column'] = array_keys($table_config['total_column']);
        $table_config['ajax_url'] = 'alive/ajax';
        return $response->write(
            $this->view()
                ->assign('table_config', $table_config)
                ->fetch('admin/ip/alive.tpl')
        );
    }

    /**
     * 后台在线 IP 页面 AJAX
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function ajax_alive(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $query = Ip::getTableDataFromAdmin(
            $request,
            static function (&$order_field) {
                if (in_array($order_field, ['user_name'])) {
                    $order_field = 'userid';
                }
                if (in_array($order_field, ['node_name', 'is_node'])) {
                    $order_field = 'nodeid';
                }
                if (in_array($order_field, ['location'])) {
                    $order_field = 'ip';
                }
            },
            static function ($query) {
                $query->where('datetime', '>=', time() - 60);
            }
        );

        $data  = [];
        $QQWry = new QQWry();
        foreach ($query['datas'] as $value) {
            /** @var Ip $value */

            $tempdata              = [];
            $tempdata['id']        = $value->id;
            $tempdata['userid']    = $value->userid;
            $tempdata['user_name'] = $value->user_name();
            $tempdata['nodeid']    = $value->nodeid;
            $tempdata['node_name'] = $value->node_name();
            $tempdata['ip']        = Tools::getRealIp($value->ip);
            $tempdata['location']  = $value->location($QQWry);
            $tempdata['datetime']  = $value->datetime();
            $tempdata['is_node']   = $value->is_node();

            $data[] = $tempdata;
        }

        return $response->withJson([
            'draw'            => $request->getParam('draw'),
            'recordsTotal'    => Ip::count(),
            'recordsFiltered' => $query['count'],
            'data'            => $data,
        ]);
    }

    /**
     * 节点被封IP
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function block(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $table_config['total_column'] = array(
            'id'        => 'ID',
            'node_name' => '节点名称',
            'ip'        => 'IP',
            'location'  => '归属地',
            'datetime'  => '时间'
        );
        $table_config['default_show_column'] = array_keys($table_config['total_column']);
        $table_config['ajax_url'] = 'block/ajax';
        return $response->write(
            $this->view()
                ->assign('table_config', $table_config)
                ->fetch('admin/ip/block.tpl')
        );
    }

    /**
     * 节点被封IP AJAX
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function ajax_block(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $query = BlockIp::getTableDataFromAdmin(
            $request,
            static function (&$order_field) {
                if (in_array($order_field, ['node_name'])) {
                    $order_field = 'nodeid';
                }
                if (in_array($order_field, ['location'])) {
                    $order_field = 'ip';
                }
            }
        );

        $data  = [];
        $QQWry = new QQWry();
        foreach ($query['datas'] as $value) {
            /** @var BlockIp $value */

            $tempdata              = [];
            $tempdata['id']        = $value->id;
            $tempdata['node_name'] = $value->node_name();
            $tempdata['ip']        = $value->ip;
            $tempdata['location']  = $value->location($QQWry);
            $tempdata['datetime']  = $value->datetime();

            $data[] = $tempdata;
        }

        return $response->withJson([
            'draw'            => $request->getParam('draw'),
            'recordsTotal'    => BlockIp::count(),
            'recordsFiltered' => $query['count'],
            'data'            => $data,
        ]);
    }

    /**
     * 解封IP
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function doUnblock(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $ip            = trim($request->getParam('ip'));
        $BIP           = BlockIp::where('ip', $ip)->delete();
        $UIP           = new UnblockIp();
        $UIP->userid   = $this->user->id;
        $UIP->ip       = $ip;
        $UIP->datetime = time();
        $UIP->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => '发送解封命令解封 ' . $ip . ' 成功'
        ]);
    }

    /**
     * 解封IP记录
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function unblock(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $table_config['total_column'] = array(
            'id'        => 'ID',
            'userid'    => '用户ID',
            'user_name' => '用户名',
            'ip'        => 'IP',
            'location'  => '归属地',
            'datetime'  => '时间'
        );
        $table_config['default_show_column'] = array_keys($table_config['total_column']);
        $table_config['ajax_url'] = 'unblock/ajax';
        return $response->write(
            $this->view()
                ->assign('table_config', $table_config)
                ->fetch('admin/ip/unblock.tpl')
        );
    }

    /**
     * 解封IP记录 AJAX
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function ajax_unblock(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $query = UnblockIp::getTableDataFromAdmin(
            $request,
            static function (&$order_field) {
                if (in_array($order_field, ['user_name'])) {
                    $order_field = 'userid';
                }
                if (in_array($order_field, ['location'])) {
                    $order_field = 'ip';
                }
            }
        );

        $data  = [];
        $QQWry = new QQWry();
        foreach ($query['datas'] as $value) {
            /** @var UnblockIp $value */

            $tempdata              = [];
            $tempdata['id']        = $value->id;
            $tempdata['userid']    = $value->userid;
            $tempdata['user_name'] = $value->user_name();
            $tempdata['ip']        = $value->ip;
            $tempdata['location']  = $value->location($QQWry);
            $tempdata['datetime']  = $value->datetime();

            $data[] = $tempdata;
        }

        return $response->withJson([
            'draw'            => $request->getParam('draw'),
            'recordsTotal'    => UnblockIp::count(),
            'recordsFiltered' => $query['count'],
            'data'            => $data,
        ]);
    }
}
