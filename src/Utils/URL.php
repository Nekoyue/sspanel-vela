<?php

namespace App\Utils;

use App\Controllers\LinkController;
use App\Models\{Node, User};

class URL
{
    /**
     * parse xxx=xxx|xxx=xxx to array(xxx => xxx, xxx => xxx)
     *
     * @param string $origin
     */
    public static function parse_args($origin): array
    {
        // parse xxx=xxx|xxx=xxx to array(xxx => xxx, xxx => xxx)
        $args_explode = explode('|', $origin);

        $return_array = [];
        foreach ($args_explode as $arg) {
            $split_point = strpos($arg, '=');

            $return_array[substr($arg, 0, $split_point)] = substr($arg, $split_point + 1);
        }

        return $return_array;
    }


    /**
     * 获取全部节点对象
     *
     * @param User  $user
     * @param mixed $sort  数值或数组
     * @param array $rules 节点筛选规则
     */
    public static function getNodes(User $user, $sort, array $rules = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Node::query();
        if (is_array($sort)) {
            $query->whereIn('sort', $sort);
        } else {
            $query->where('sort', $sort);
        }
        if (!$user->is_admin) {
            $group = ($user->node_group != 0 ? [0, $user->node_group] : [0]);
            $query->whereIn('node_group', $group)
                ->where('node_class', '<=', $user->class);
        }
        // 等级筛选
        if (isset($rules['content']['class']) && count($rules['content']['class']) > 0) {
            $query->whereIn('node_class', $rules['content']['class']);
        }
        if (isset($rules['content']['noclass']) && count($rules['content']['noclass']) > 0) {
            $query->whereNotIn('node_class', $rules['content']['noclass']);
        }
        // 等级筛选 end
        $nodes = $query->where('type', '1')
            ->orderBy('name')->get();

        return $nodes;
    }

    /**
     * 获取全部节点
     *
     * ```
     * $Rule = [
     *      'type'    => 'all | trojan',
     *      'emoji'   => false,
     *      'is_mu'   => 1,
     *      'content' => [
     *          'noclass' => [0, 1, 2],
     *          'class'   => [0, 1, 2],
     *          'regex'   => '.*香港.*HKBN.*',
     *      ]
     * ]
     * ```
     *
     * @param User  $user 用户
     * @param array $Rule 节点筛选规则
     */
    public static function getNew_AllItems(User $user, array $Rule): array
    {
        $is_ss = [0];
        $is_mu = ($Rule['is_mu'] ?? (int)$_ENV['mergeSub']);
        $emoji = ($Rule['emoji'] ?? false);

        switch ($Rule['type']) {
            case 'trojan':
                $sort = [14];
                break;
            default:
                $Rule['type'] = 'all';
                $sort = [0, 14];
                break;
        }

        // 获取节点
        $nodes = self::getNodes($user, $sort, $Rule);

        // 单端口 sort = 9
        $mu_nodes = [];
        if ($is_mu != 0 && in_array($Rule['type'], ['all'])) {
            $mu_node_query = Node::query();
            $mu_node_query->where('sort', 9)->where('type', '1');
            if ($is_mu != 1) {
                $mu_node_query->where('server', $is_mu);
            }
            if (!$user->is_admin) {
                $group = ($user->node_group != 0 ? [0, $user->node_group] : [0]);
                $mu_node_query->where('node_class', '<=', $user->class)
                    ->whereIn('node_group', $group);
            }
            $mu_nodes = $mu_node_query->get();
        }

        $return_array = [];
        foreach ($nodes as $node) {
            if (isset($Rule['content']['regex']) && $Rule['content']['regex'] != '') {
                // 节点名称筛选
                if (
                    ConfGenerate::getMatchProxy(
                        [
                            'remark' => $node->name
                        ],
                        [
                            'content' => [
                                'regex' => $Rule['content']['regex']
                            ]
                        ]
                    ) === null
                ) {
                    continue;
                }
            }
            // 筛选 End

            // 其他类型单端口节点
            if (in_array($node->sort, [
                14])) {
                $node_class = [
                    14 => 'getTrojanItem',          // Trojan
                ];
                $class = $node_class[$node->sort];
                $item = $node->$class($user, 0, 0, $emoji);
                if ($item != null) {
                    $return_array[] = $item;
                }
                continue;
            }
            // 其他类型单端口节点 End

            // SS 节点
            if (in_array($node->sort, [0])) {
                // 节点非只启用单端口 && 只获取普通端口
                if ($node->mu_only != 1 && ($is_mu == 0 || ($is_mu != 0 && $_ENV['mergeSub'] === true))) {
                    foreach ($is_ss as $ss) {
                        $item = $node->getItem($user, 0, $ss, $emoji);
                        if ($item != null) {
                            $return_array[] = $item;
                        }
                    }
                }
                // 获取 SS 普通端口 End

                // 非只启用普通端口 && 获取单端口
                if ($node->mu_only != -1 && $is_mu != 0) {
                    foreach ($is_ss as $ss) {
                        foreach ($mu_nodes as $mu_node) {
                            $item = $node->getItem($user, $mu_node->server, $ss, $emoji);
                            if ($item != null) {
                                $return_array[] = $item;
                            }
                        }
                    }
                }
                // 获取 SS 单端口 End
            }
            // SS 节点 End
        }

        return $return_array;
    }

    /**
     * 获取全部节点 Url
     *
     * ```
     *  $Rule = [
     *      'type'    => 'vmess',
     *      'emoji'   => false,
     *      'is_mu'   => 1,
     *      'content' => [
     *          'noclass' => [0, 1, 2],
     *          'class'   => [0, 1, 2],
     *          'regex'   => '.*香港.*HKBN.*',
     *      ]
     *  ]
     * ```
     *
     * @param User  $user 用户
     * @param array $Rule 节点筛选规则
     */
    public static function get_NewAllUrl(User $user, array $Rule): string
    {
        $return_url = '';
        if (strtotime($user->expire_in) < time()) {
            return $return_url;
        }
        $items = URL::getNew_AllItems($user, $Rule);
        foreach ($items as $item) {
            $out = LinkController::getListItem($item, $Rule['type']);

            if ($out !== null) {
                $return_url .= $out . PHP_EOL;
            }
        }
        return $return_url;
    }

    public static function getItemUrl($item, $is_ss)
    {
        return AppURI::getItemUrl($item, $is_ss);
    }



    /**
     * 获取 Trojan 全部节点
     *
     * @param User $user 用户
     * @param bool $emoji
     */
    public static function getAllTrojan($user, $emoji = false): array
    {
        $return_array = array();
        $nodes = self::getNodes($user, 14);
        foreach ($nodes as $node) {
            $item = $node->getTrojanItem($user, 0, 0, $emoji);
            if ($item != null) {
                $return_array[] = $item;
            }
        }

        return $return_array;
    }

    /**
     * 获取 Trojan URL
     *
     * @param User $user 用户
     * @param Node $node
     */
    public static function get_trojan_url($user, $node): string
    {
        $server = $node->getTrojanItem($user);
        $return = 'trojan://' . $server['passwd'] . '@' . $server['address'] . ':' . $server['port'];
        if ($server['host'] != $server['address']) {
            $return .= '?peer=' . $server['host'] . '&sni=' . $server['host'];
        }
        return $return . '#' . rawurlencode($node->name);
    }

    public static function cloneUser(User $user): User
    {
        return clone $user;
    }
}
