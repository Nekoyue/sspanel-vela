<?php
namespace App\Controllers\Mod_Mu;

use Slim\Http\{
    ServerRequest,
    Response
};
use App\Models\{
    Node,
    StreamMedia,
    NodeInfoLog
};
use App\Utils\Tools;
use App\Services\Config;
use App\Controllers\BaseController;
use Psr\Http\Message\ResponseInterface;

class NodeController extends BaseController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     */
    public function saveReport(ServerRequest $request, Response $response, array $args): void
    {
        // $request_ip = $_SERVER["REMOTE_ADDR"];
        $node_id = $request->getParam('node_id');
        $content = $request->getParam('content');
        $result = json_decode(base64_decode($content), true);

        /* $node = Node::where('node_ip', $request_ip)->first();
        if ($node != null) {
            $report = new StreamMedia;
            $report->node_id = $node->id;
            $report->result = json_encode($result);
            $report->created_at = time();
            $report->save();
            die('ok');
        } */

        $report = new StreamMedia;
        $report->node_id = $node_id;
        $report->result = json_encode($result);
        $report->created_at = time();
        $report->save();
        die('ok');
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function info(ServerRequest $request, Response $response, array $args): Response|ResponseInterface
    {
        $node_id = $args['id'];
        if ($node_id == '0') {
            $node = Node::where('node_ip', $_SERVER['REMOTE_ADDR'])->first();
            $node_id = $node->id;
        }
        $load = $request->getParam('load');
        $uptime = $request->getParam('uptime');
        $log = new NodeInfoLog();
        $log->node_id = $node_id;
        $log->load = $load;
        $log->uptime = $uptime;
        $log->log_time = time();
        if (!$log->save()) {
            $res = [
                'ret' => 0,
                'data' => 'update failed',
            ];
            return $response->withJson($res);
        }
        $res = [
            'ret' => 1,
            'data' => 'ok',
        ];
        return $response->withJson($res);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     */
    public function get_info(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node_id = $args['id'];
        if ($node_id == '0') {
            $node = Node::where('node_ip', $_SERVER['REMOTE_ADDR'])->first();
            $node_id = $node->id;
        }
        $node = Node::find($node_id);
        if ($node == null) {
            $res = [
                'ret' => 0
            ];
            return $response->withJson($res);
        }
        if (in_array($node->sort, [0])) {
            $node_explode = explode(';', $node->server);
            $node_server = $node_explode[0];
        } else {
            $node_server = $node->server;
        }
        $data = [
            'node_group' => $node->node_group,
            'node_class' => $node->node_class,
            'node_speedlimit' => $node->node_speedlimit,
            'traffic_rate' => $node->traffic_rate,
            'mu_only' => $node->mu_only,
            'sort' => $node->sort,
            'server' => $node_server,
            'custom_config' => json_decode($node->custom_config, true, JSON_UNESCAPED_SLASHES),
            'disconnect_time' => $_ENV['disconnect_time'],
            'type' => 'SSPanel-UIM',
            'version' => '2021.11'
        ];

        $res = [
            'ret' => 1,
            'data' => $data
        ];
        $header_etag = $request->getHeaderLine('IF_NONE_MATCH');
        $etag = Tools::etag($data);
        if ($header_etag == $etag) {
            return $response->withStatus(304);
        }

        return $response->withHeader('ETAG', $etag)->withJson($res);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     */
    public function get_all_info(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $nodes = Node::where('node_ip', '<>', null)->where(
            static function ($query) {
                $query->where('sort', '=', 0)
                    ->orWhere('sort', '=', 10)
                    ->orWhere('sort', '=', 12)
                    ->orWhere('sort', '=', 13)
                    ->orWhere('sort', '=', 14);
            }
        )->get();
        $res = [
            'ret' => 1,
            'data' => $nodes
        ];

        $header_etag = $request->getHeaderLine('IF_NONE_MATCH');
        $etag = Tools::etag($nodes);
        if ($header_etag == $etag) {
            return $response->withStatus(304);
        }

        return $response->withHeader('ETAG', $etag)->withJson($res);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function getConfig(ServerRequest $request, Response $response, array $args): Response|ResponseInterface
    {
        $data = $request->getParsedBody();
        switch ($data['type']) {
            case ('database'):
                $db_config = Config::getDbConfig();
                $db_config['host'] = $this->getServerIP();
                $res = [
                    'ret' => 1,
                    'data' => $db_config,
                ];
                break;
            case ('webapi'):
                $webapiConfig = [];
                #todo
        }
        return $response->withJson($res);
    }

    private function getServerIP()
    {
        if (isset($_SERVER)) {
            if ($_SERVER['SERVER_ADDR']) {
                $serverIP = $_SERVER['SERVER_ADDR'];
            } else {
                $serverIP = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $serverIP = getenv('SERVER_ADDR');
        }
        return $serverIP;
    }
}
