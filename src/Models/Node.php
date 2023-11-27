<?php

namespace App\Models;

/**
 * Node Model
 *
 * @property-read   int     $id         id
 * @property        string  $name       Display name
 * @property        bool    $type       If node display @todo Correct column name and type
 * @property        string  $server     Domain
 * @property        string  $method     Crypt method @deprecated
 * @property        string  $info       Infomation
 * @property        string  $status     Status description
 * @property        int     $sort       Node type @todo Correct column name to `type`
 * @property        int     $custom_method  Customs node crypt @deprecated
 * @property        float   $traffic_rate   Node traffic rate
 * @todo More property
 * @property        bool    $online     If node is online
 * @property        bool    $gfw_block  If node is blocked by GFW
 */

use App\Services\Config;
use App\Utils\{Tools, URL};

class Node extends Model
{
    protected $connection = 'default';

    protected $table = 'node';

    protected $casts = [
        'node_speedlimit' => 'float',
        'traffic_rate'    => 'float',
        'mu_only'         => 'int',
        'sort'            => 'int',
        'type'            => 'bool',
        'node_heartbeat'  => 'int',
    ];

    /**
     * 节点是否显示和隐藏
     */
    public function type(): string
    {
        return $this->type ? '显示' : '隐藏';
    }

    /**
     * 节点类型
     */
    public function sort(): string
    {
        switch ($this->sort) {
            case 14:
                $sort = 'Trojan';
                break;
            default:
                $sort = '系统保留';
        }
        return $sort;
    }

    /**
     * 单端口多用户启用类型
     */
    public function mu_only(): string
    {
        switch ($this->mu_only) {
            case -1:
                $mu_only = '只启用普通端口';
                break;
            case 0:
                $mu_only = '单端口多用户与普通端口并存';
                break;
            case 1:
                $mu_only = '只启用单端口多用户';
                break;
            default:
                $mu_only = '错误类型';
        }
        return $mu_only;
    }

    /**
     * 节点对应的国旗
     *
     * @return string [国家].png OR unknown.png
     */
    public function get_node_flag(): string
    {
        $regex   = $_ENV['flag_regex'];
        $matches = [];
        preg_match($regex, $this->name, $matches);
        return isset($matches[0]) ? $matches[0] . '.png' : 'unknown.png';
    }

    /**
     * 节点最后活跃时间
     */
    public function node_heartbeat(): string
    {
        return date('Y-m-d H:i:s', $this->node_heartbeat);
    }

    public function getLastNodeInfoLog()
    {
        $log = NodeInfoLog::where('node_id', $this->id)->orderBy('id', 'desc')->first();
        if ($log == null) {
            return null;
        }
        return $log;
    }

    public function getNodeUptime()
    {
        $log = $this->getLastNodeInfoLog();
        if ($log == null) {
            return '暂无数据';
        }
        return Tools::secondsToTime((int) $log->uptime);
    }

    public function getNodeUpRate()
    {
        $log = NodeOnlineLog::where('node_id', $this->id)->where('log_time', '>=', time() - 86400)->count();
        return $log / 1440;
    }

    public function getNodeLoad()
    {
        $log = NodeInfoLog::where('node_id', $this->id)->orderBy('id', 'desc')->whereRaw('`log_time`%1800<60')->limit(48)->get();
        return $log;
    }

    public function getNodeAlive()
    {
        $log = NodeOnlineLog::where('node_id', $this->id)->orderBy('id', 'desc')->whereRaw('`log_time`%1800<60')->limit(48)->get();
        return $log;
    }

    /**
     * 获取节点 5 分钟内最新的在线人数
     */
    public function get_node_online_user_count(): int
    {
        if (in_array($this->sort, [9])) {
            return -1;
        }
        $log = NodeOnlineLog::where('node_id', $this->id)->where('log_time', '>', time() - 300)->orderBy('id', 'desc')->first();
        if ($log == null) {
            return 0;
        }
        return $log->online_user;
    }

    /**
     * 获取节点在线状态
     *
     * @return int 0 = new node OR -1 = offline OR 1 = online
     */
    public function get_node_online_status(): int
    {
        // 类型 9 或者心跳为 0
        if ($this->node_heartbeat == 0 || in_array($this->sort, [9])) {
            return 0;
        }
        return $this->node_heartbeat + 300 > time() ? 1 : -1;
    }

    /**
     * 获取节点最新负载
     */
    public function get_node_latest_load(): int
    {
        $log = NodeInfoLog::where('node_id', $this->id)->where('log_time', '>', time() - 300)->orderBy('id', 'desc')->first();
        if ($log == null) {
            return -1;
        }
        return (explode(' ', $log->load))[0] * 100;
    }

    /**
     * 获取节点最新负载文本信息
     */
    public function get_node_latest_load_text(): string
    {
        $load = $this->get_node_latest_load();
        return $load == -1 ? 'N/A' : $load . '%';
    }

    /**
     * 获取节点速率文本信息
     */
    public function get_node_speedlimit(): string
    {
        if ($this->node_speedlimit == 0.0) {
            return 0;
        } elseif ($this->node_speedlimit >= 1024.00) {
            return round($this->node_speedlimit / 1024.00, 1) . 'Gbps';
        } else {
            return $this->node_speedlimit . 'Mbps';
        }
    }

    /**
     * 节点是在线的
     */
    public function isNodeOnline(): ?bool
    {
        if ($this->node_heartbeat === 0) {
            return false;
        }
        return $this->node_heartbeat > time() - 300;
    }

    /**
     * 节点流量已耗尽
     */
    public function isNodeTrafficOut(): bool
    {
        return !($this->node_bandwidth_limit == 0 || $this->node_bandwidth < $this->node_bandwidth_limit);
    }

    /**
     * 节点是可用的，即流量未耗尽并且在线
     */
    public function isNodeAccessable(): bool
    {
        return $this->isNodeTrafficOut() == false && $this->isNodeOnline() == true;
    }

    /**
     * 更新节点 IP
     *
     * @param string $server_name
     */
    public function changeNodeIp(string $server_name): bool
    {
        if (!Tools::is_ip($server_name)) {
            $ip = gethostbyname($server_name);
            if ($ip == '') {
                return false;
            }
        } else {
            $ip = $server_name;
        }
        $this->node_ip = $ip;
        return true;
    }

    /**
     * 获取节点 IP
     */
    public function getNodeIp(): string
    {
        $node_ip_str   = $this->node_ip;
        $node_ip_array = explode(',', $node_ip_str);
        return $node_ip_array[0];
    }

    /**
     * 获取出口地址 | 用于节点IP获取的地址
     */
    public function get_out_address(): string
    {
        return explode(';', $this->server)[0];
    }

    /**
     * 获取入口地址
     */
    public function get_entrance_address(): string
    {
        if ($this->sort == 13) {
            $server = Tools::ssv2Array($this->server);
            return $server['add'];
        }
        $explode = explode(';', $this->server);
        if (in_array($this->sort, [0]) && isset($explode[1])) {
            if (stripos($explode[1], 'server=') !== false) {
                return URL::parse_args($explode[1])['server'];
            }
        }
        return $explode[0];
    }

    /**
     * 获取偏移后的端口
     *
     * @param mixed $port
     */
    public function getOffsetPort($port)
    {
        return Tools::OutPort($this->server, $this->name, $port)['port'];
    }


    /**
     * Trojan 节点
     *
     * @param User $user 用户
     * @param int  $mu_port
     * @param int  $is_ss
     * @param bool $emoji
     */
    public function getTrojanItem(User $user, int $mu_port = 0, int $is_ss = 0, bool $emoji = false): array
    {
        $server = explode(';', $this->server);
        $opt    = [];
        if (isset($server[1])) {
            $opt = URL::parse_args($server[1]);
        }
        $item['remark']   = ($emoji ? Tools::addEmoji($this->name) : $this->name);
        $item['type']     = 'trojan';
        $item['address']  = $server[0];
        $item['port']     = (isset($opt['port']) ? (int) $opt['port'] : 443);
        $item['passwd']   = $user->uuid;
        $item['host']     = $item['address'];
        $item['net']      = (isset($opt['grpc']) ? "grpc" : '');
        $item['servicename'] = (isset($opt['servicename']) ? $opt['servicename'] : '');
        $item['flow']     = (isset($opt['flow']) ? $opt['flow'] : '');
        $xtls             = (isset($opt['enable_xtls']) ? $opt['enable_xtls'] : '');
        if($xtls == 'true'){
          $item['tls'] =  'xtls';
        }else {
          $item['tls'] =  'tls';
        }
        $item['allow_insecure'] = (isset($opt['allow_insecure']) ? $opt['allow_insecure'] : '');
        if (isset($opt['host'])) {
          $item['host'] = $opt['host'];
        }
        return $item;
    }



}
