<?php

namespace App\Controllers;

use App\Models\InviteCode;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 *  HomeController
 */
class HomeController extends BaseController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('index.tpl'));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function code(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $codes = InviteCode::where('user_id', '=', '0')->take(10)->get();
        return $response->write($this->view()->assign('codes', $codes)->fetch('code.tpl'));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function tos(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('tos.tpl'));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function staff(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('staff.tpl'));
    }


    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function page404(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('404.tpl'));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function page405(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('405.tpl'));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws \SmartyException
     */
    public function page500(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('500.tpl'));
    }
}
