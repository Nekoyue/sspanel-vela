<?php

namespace App\Controllers\User;

use App\Controllers\UserController;
use App\Models\{
    Node,
    User
};
use App\Utils\{
    URL,
    Tools
};
use Slim\Http\{
    ServerRequest,
    Response
};
use Psr\Http\Message\ResponseInterface;

/**
 *  User NodeController
 */
class NodeController extends UserController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function user_node_page(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user  = $this->user;
        $query = Node::query();
        $query->where('type', 1)->whereNotIn('sort', [9]);
        if (!$user->is_admin) {
            $group = ($user->node_group != 0 ? [0, $user->node_group] : [0]);
            $query->whereIn('node_group', $group);
        }
        $nodes    = $query->orderBy('node_class')->orderBy('name')->get();
        $all_node = [];
        foreach ($nodes as $node) {
            /** @var Node $node */

            $array_node                   = [];
            $array_node['id']             = $node->id;
            $array_node['name']           = $node->name;
            $array_node['class']          = $node->node_class;
            $array_node['info']           = $node->info;
            $array_node['flag']           = $node->get_node_flag();
            $array_node['online_user']    = $node->get_node_online_user_count();
            $array_node['online']         = $node->get_node_online_status();
            $array_node['latest_load']    = $node->get_node_latest_load_text();
            $array_node['traffic_rate']   = $node->traffic_rate;
            $array_node['status']         = $node->status;
            $array_node['traffic_used']   = (int) Tools::flowToGB($node->node_bandwidth);
            $array_node['traffic_limit']  = (int) Tools::flowToGB($node->node_bandwidth_limit);
            $array_node['bandwidth']      = $node->get_node_speedlimit();

            $all_connect = [];
            if (in_array($node->sort, [0])) {
                if ($node->mu_only != 1) {
                    $all_connect[] = 0;
                }
                if ($node->mu_only != -1) {
                    $mu_node_query = Node::query();
                    $mu_node_query->where('sort', 9)->where('type', '1');
                    if (!$user->is_admin) {
                        $mu_node_query->where('node_class', '<=', $user->class)->whereIn('node_group', $group);
                    }
                    $mu_nodes = $mu_node_query->get();
                    foreach ($mu_nodes as $mu_node) {
                        if (User::where('port', $mu_node->server)->where('is_multi_user', '<>', 0)->first() != null) {
                            $all_connect[] = $node->getOffsetPort($mu_node->server);
                        }
                    }
                }
            } else {
                $all_connect[] = 0;
            }
            $array_node['connect'] = $all_connect;

            $all_node[$node->node_class + 1000][] = $array_node;
        }

        return $response->write(
            $this->view()
                ->assign('nodes', $all_node)
                ->fetch('user/node/index.tpl')
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function user_node_ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id           = $args['id'];
        $point_node   = Node::find($id);
        $prefix       = explode(' - ', $point_node->name);

        return $response->write(
            $this->view()
                ->assign('point_node', $point_node)
                ->assign('prefix', $prefix[0])
                ->assign('id', $id)
                ->fetch('user/node/nodeajax.tpl')
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function user_node_info(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $node = Node::find($args['id']);
        if ($node == null) {
            return $response->write('非法访问');
        }
        if (!$user->is_admin) {
            if ($user->node_group != $node->node_group && $node->node_group != 0) {
                return $response->write('无权查看该分组的节点');
            }
            if ($user->class < $node->node_class) {
                return $response->write('无权查看该等级的节点');
            }
        }
        switch ($node->sort) {
            case 14:
                $server = $node->getTrojanItem($user);
                $nodes  = [
                    'url'  => URL::get_trojan_url($user, $node),
                    'info' => [
                        '连接地址：' => $server['address'],
                        '连接端口：' => $server['port'],
                        '连接密码：' => $server['passwd'],
                    ],
                ];
                if ($server['host'] != $server['address']) {
                    $nodes['info']['HOST&PEER：'] = $server['host'];
                }

                return $response->write(
                    $this->view()
                    ->assign('node', $nodes)
                        ->fetch('user/node/node_trojan.tpl')
                );
            default:
                return $response->write(404);
        }
    }
}
