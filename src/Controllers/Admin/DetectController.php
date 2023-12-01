<?php

namespace App\Controllers\Admin;

use App\Controllers\AdminController;
use App\Models\DetectLog;
use App\Models\DetectRule;
use App\Utils\Telegram;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\{
    ServerRequest,
    Response
};
use Telegram\Bot\Exceptions\TelegramSDKException;

class DetectController extends AdminController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function index(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $table_config['total_column'] = array(
            'op'    => '操作',
            'id'    => 'ID',
            'name'  => '名称',
            'text'  => '介绍',
            'regex' => '正则表达式',
            'type'  => '类型'
        );
        $table_config['default_show_column'] = array_keys($table_config['total_column']);
        $table_config['ajax_url'] = 'detect/ajax';
        return $response->write(
            $this->view()
                ->assign('table_config', $table_config)
                ->fetch('admin/detect/index.tpl')
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function ajax_rule(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $query = DetectRule::getTableDataFromAdmin(
            $request,
            static function (&$order_field) {
                if (in_array($order_field, ['op'])) {
                    $order_field = 'id';
                }
            }
        );

        $data  = [];
        foreach ($query['datas'] as $value) {
            /** @var DetectRule $value */

            $tempdata             = [];
            $tempdata['op']       = '<a class="btn btn-brand" href="/admin/detect/' . $value->id . '/edit">编辑</a> <a class="btn btn-brand-accent" id="delete" value="' . $value->id . '" href="javascript:void(0);" onClick="delete_modal_show(\'' . $value->id . '\')">删除</a>';
            $tempdata['id']       = $value->id;
            $tempdata['name']     = $value->name;
            $tempdata['text']     = $value->text;
            $tempdata['regex']    = $value->regex;
            $tempdata['type']     = $value->type();

            $data[] = $tempdata;
        }

        return $response->withJson([
            'draw'            => $request->getParam('draw'),
            'recordsTotal'    => DetectRule::count(),
            'recordsFiltered' => $query['count'],
            'data'            => $data,
        ]);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function create(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        return $response->write(
            $this->view()
                ->fetch('admin/detect/add.tpl')
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws TelegramSDKException
     */
    public function add(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $rule = new DetectRule();
        $rule->name = $request->getParam('name');
        $rule->text = $request->getParam('text');
        $rule->regex = $request->getParam('regex');
        $rule->type = $request->getParam('type');

        if (!$rule->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '添加失败'
            ]);
        }

        Telegram::SendMarkdown('有新的审计规则：' . $rule->name);
        return $response->withJson([
            'ret' => 1,
            'msg' => '添加成功'
        ]);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function edit(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $id = $args['id'];
        $rule = DetectRule::find($id);
        return $response->write(
            $this->view()
                ->assign('rule', $rule)
                ->fetch('admin/detect/edit.tpl')
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws TelegramSDKException
     */
    public function update(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $id = $args['id'];
        $rule = DetectRule::find($id);

        $rule->name = $request->getParam('name');
        $rule->text = $request->getParam('text');
        $rule->regex = $request->getParam('regex');
        $rule->type = $request->getParam('type');

        if (!$rule->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '修改失败'
            ]);
        }
        Telegram::SendMarkdown('规则更新：' . PHP_EOL . $request->getParam('name'));
        return $response->withJson([
            'ret' => 1,
            'msg' => '修改成功'
        ]);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function delete(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $id = $request->getParam('id');
        $rule = DetectRule::find($id);
        if (!$rule->delete()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '删除失败'
            ]);
        }
        return $response->withJson([
            'ret' => 1,
            'msg' => '删除成功'
        ]);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws \SmartyException
     */
    public function log(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $table_config['total_column'] = array(
            'id'          => 'ID',
            'user_id'     => '用户ID',
            'user_name'   => '用户名',
            'node_id'     => '节点ID',
            'node_name'   => '节点名',
            'list_id'     => '规则ID',
            'rule_name'   => '规则名',
            'rule_text'   => '规则描述',
            'rule_regex'  => '规则正则表达式',
            'rule_type'   => '规则类型',
            'datetime'    => '时间'
        );
        $table_config['default_show_column'] = array_keys($table_config['total_column']);
        $table_config['ajax_url'] = 'log/ajax';
        return $response->write(
            $this->view()
                ->assign('table_config', $table_config)
                ->fetch('admin/detect/log.tpl')
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function ajax_log(ServerRequest $request, Response $response, array $args): Response|\Psr\Http\Message\ResponseInterface
    {
        $query = DetectLog::getTableDataFromAdmin(
            $request,
            static function (&$order_field) {
                if (in_array($order_field, ['node_name'])) {
                    $order_field = 'node_id';
                }
                if (in_array($order_field, ['rule_name', 'rule_text', 'rule_regex', 'rule_type'])) {
                    $order_field = 'list_id';
                }
                if (in_array($order_field, ['user_name'])) {
                    $order_field = 'user_id';
                }
            }
        );

        $data  = [];
        foreach ($query['datas'] as $value) {
            /** @var DetectLog $value */

            if ($value->rule() == null) {
                DetectLog::rule_is_null($value);
                continue;
            }
            if ($value->node() == null) {
                DetectLog::node_is_null($value);
                continue;
            }
            if ($value->user() == null) {
                DetectLog::user_is_null($value);
                continue;
            }
            $tempdata               = [];
            $tempdata['id']         = $value->id;
            $tempdata['user_id']    = $value->user_id;
            $tempdata['user_name']  = $value->user_name();
            $tempdata['node_id']    = $value->node_id;
            $tempdata['node_name']  = $value->node_name();
            $tempdata['list_id']    = $value->list_id;
            $tempdata['rule_name']  = $value->rule_name();
            $tempdata['rule_text']  = $value->rule_text();
            $tempdata['rule_regex'] = $value->rule_regex();
            $tempdata['rule_type']  = $value->rule_type();
            $tempdata['datetime']   = $value->datetime();

            $data[] = $tempdata;
        }

        return $response->withJson([
            'draw'            => $request->getParam('draw'),
            'recordsTotal'    => DetectLog::count(),
            'recordsFiltered' => $query['count'],
            'data'            => $data,
        ]);
    }
}
