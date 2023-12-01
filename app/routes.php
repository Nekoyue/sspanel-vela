<?php

declare(strict_types=1);

use App\Middleware\{Admin, Auth, AuthorizationBearer, Guest, Mod_Mu};
use Slim\Routing\RouteCollectorProxy;

return function (Slim\App $app) {
    // Home
    $app->get('/',          App\Controllers\HomeController::class . ':index');
    $app->get('/404',       App\Controllers\HomeController::class . ':page404');
    $app->get('/405',       App\Controllers\HomeController::class . ':page405');
    $app->get('/500',       App\Controllers\HomeController::class . ':page500');
    $app->get('/tos',       App\Controllers\HomeController::class . ':tos');
    $app->get('/staff',     App\Controllers\HomeController::class . ':staff');

    // other
    $app->post('/notify',               App\Controllers\HomeController::class . ':notify');

    // Telegram
    $app->post('/telegram_callback',    App\Controllers\CallbackController::class . ':telegram');

    // User Center
    $app->group('/user', static function (RouteCollectorProxy $group): void {
        $group->get('', App\Controllers\UserController::class . ':index');
        $group->get('/', App\Controllers\UserController::class . ':index');

        $group->post('/checkin', App\Controllers\UserController::class . ':doCheckin');

        $group->get('/announcement', App\Controllers\UserController::class . ':announcement');
        $group->get('/media', App\Controllers\UserController::class . ':media');

        $group->get('/donate', App\Controllers\UserController::class . ':donate');
        $group->get('/profile', App\Controllers\UserController::class . ':profile');
        $group->get('/invite', App\Controllers\UserController::class . ':invite');
        $group->get('/disable', App\Controllers\UserController::class . ':disable');

        $group->get('/node', App\Controllers\User\NodeController::class . ':user_node_page');
        $group->get('/node/{id}/ajax', App\Controllers\User\NodeController::class . ':user_node_ajax');
        $group->get('/node/{id}', App\Controllers\User\NodeController::class . ':user_node_info');

        $group->get('/detect', App\Controllers\UserController::class . ':detect_index');
        $group->get('/detect/log', App\Controllers\UserController::class . ':detect_log');

        $group->get('/shop', App\Controllers\UserController::class . ':shop');
        $group->post('/coupon_check', App\Controllers\UserController::class . ':CouponCheck');
        $group->post('/buy', App\Controllers\UserController::class . ':buy');
        $group->post('/buy_traffic_package', App\Controllers\UserController::class . ':buy_traffic_package');

        $group->get('/ticket', App\Controllers\User\TicketController::class . ':ticket');
        $group->get('/ticket/create', App\Controllers\User\TicketController::class . ':ticket_create');
        $group->post('/ticket', App\Controllers\User\TicketController::class . ':ticket_add');
        $group->get('/ticket/{id}/view', App\Controllers\User\TicketController::class . ':ticket_view');
        $group->put('/ticket/{id}', App\Controllers\User\TicketController::class . ':ticket_update');

        $group->post('/buy_invite', App\Controllers\UserController::class . ':buyInvite');
        $group->post('/custom_invite', App\Controllers\UserController::class . ':customInvite');
        $group->get('/edit', App\Controllers\UserController::class . ':edit');
        $group->post('/email', App\Controllers\UserController::class . ':updateEmail');
        $group->post('/username', App\Controllers\UserController::class . ':updateUsername');
        $group->post('/password', App\Controllers\UserController::class . ':updatePassword');
        $group->post('/send', App\Controllers\AuthController::class . ':sendVerify');
        $group->post('/wechat', App\Controllers\UserController::class . ':updateWechat');
        $group->post('/theme', App\Controllers\UserController::class . ':updateTheme');
        $group->post('/mail', App\Controllers\UserController::class . ':updateMail');
        $group->post('/sspwd', App\Controllers\UserController::class . ':updateSsPwd');
        $group->post('/method', App\Controllers\UserController::class . ':updateMethod');
        $group->post('/hide', App\Controllers\UserController::class . ':updateHide');
        $group->get('/sys', App\Controllers\UserController::class . ':sys');
        $group->get('/trafficlog', App\Controllers\UserController::class . ':trafficLog');
        $group->get('/kill', App\Controllers\UserController::class . ':kill');
        $group->post('/kill', App\Controllers\UserController::class . ':handleKill');
        $group->get('/logout', App\Controllers\UserController::class . ':logout');
        $group->get('/backtoadmin', App\Controllers\UserController::class . ':backtoadmin');
        $group->get('/code', App\Controllers\UserController::class . ':code');

        $group->get('/code_check', App\Controllers\UserController::class . ':code_check');
        $group->post('/code', App\Controllers\UserController::class . ':codepost');
        $group->post('/gacheck', App\Controllers\UserController::class . ':GaCheck');
        $group->post('/gaset', App\Controllers\UserController::class . ':GaSet');
        $group->get('/gareset', App\Controllers\UserController::class . ':GaReset');
        $group->get('/telegram_reset', App\Controllers\UserController::class . ':telegram_reset');
        $group->post('/resetport', App\Controllers\UserController::class . ':ResetPort');
        $group->post('/specifyport', App\Controllers\UserController::class . ':SpecifyPort');
        $group->post('/unblock', App\Controllers\UserController::class . ':Unblock');
        $group->get('/bought', App\Controllers\UserController::class . ':bought');
        $group->delete('/bought', App\Controllers\UserController::class . ':deleteBoughtGet');
        $group->get('/url_reset', App\Controllers\UserController::class . ':resetURL');
        $group->put('/invite', App\Controllers\UserController::class . ':resetInviteURL');

        $group->get('/order', App\Controllers\UserController::class . ':user_order');
        $group->get('/product', App\Controllers\UserController::class . ':product_index');

        // 订阅记录
        $group->get('/subscribe_log', App\Controllers\UserController::class . ':subscribe_log');

        // getPcClient
        $group->get('/getPcClient', App\Controllers\UserController::class . ':getPcClient');

        //Reconstructed Payment System
        $group->post('/payment/purchase/{type}', App\Services\Payment::class . ':purchase');
        $group->get('/payment/purchase/{type}', App\Services\Payment::class . ':purchase');
        $group->get('/payment/return/{type}', App\Services\Payment::class . ':returnHTML');

    })->add(new Auth());

    $app->group('/payment', static function (RouteCollectorProxy $group): void {
        $group->get('/notify/{type}', App\Services\Payment::class . ':notify');
        $group->post('/notify/{type}', App\Services\Payment::class . ':notify');
        $group->post('/status/{type}', App\Services\Payment::class . ':getStatus');
        // $group->post('/coinpay/notify',  App\Services\CoinPayment::class. ':notify');
    });

    // Auth
    $app->group('/auth', static function (RouteCollectorProxy $group): void {
        $group->get('/login', App\Controllers\AuthController::class . ':login');
        $group->post('/qrcode_check', App\Controllers\AuthController::class . ':qrcode_check');
        $group->post('/login', App\Controllers\AuthController::class . ':loginHandle');
        $group->post('/qrcode_login', App\Controllers\AuthController::class . ':qrcode_loginHandle');
        $group->get('/register', App\Controllers\AuthController::class . ':register');
        $group->post('/register', App\Controllers\AuthController::class . ':registerHandle');
        $group->post('/send', App\Controllers\AuthController::class . ':sendVerify');
        $group->get('/logout', App\Controllers\AuthController::class . ':logout');
        $group->get('/telegram_oauth', App\Controllers\AuthController::class . ':telegram_oauth');
        $group->get('/login_getCaptcha', App\Controllers\AuthController::class . ':getCaptcha');
    })->add(new Guest());

    // Password
    $app->group('/password', static function (RouteCollectorProxy $group): void {
        $group->get('/reset', App\Controllers\PasswordController::class . ':reset');
        $group->post('/reset', App\Controllers\PasswordController::class . ':handleReset');
        $group->get('/token/{token}', App\Controllers\PasswordController::class . ':token');
        $group->post('/token/{token}', App\Controllers\PasswordController::class . ':handleToken');
    })->add(new Guest());

    // Admin
    $app->group('/admin', static function (RouteCollectorProxy $group): void {
        $group->get('', App\Controllers\AdminController::class . ':index');
        $group->get('/', App\Controllers\AdminController::class . ':index');

        $group->get('/sys', App\Controllers\AdminController::class . ':sys');
        $group->get('/invite', App\Controllers\AdminController::class . ':invite');
        $group->post('/invite', App\Controllers\AdminController::class . ':addInvite');
        $group->post('/chginvite', App\Controllers\AdminController::class . ':chgInvite');
        $group->post('/payback/ajax', App\Controllers\AdminController::class . ':ajax_payback');

        // Node Mange
        $group->get('/node', App\Controllers\Admin\NodeController::class . ':index');
        $group->get('/node/create', App\Controllers\Admin\NodeController::class . ':create');
        $group->post('/node', App\Controllers\Admin\NodeController::class . ':add');
        $group->get('/node/{id}/edit', App\Controllers\Admin\NodeController::class . ':edit');
        $group->put('/node/{id}', App\Controllers\Admin\NodeController::class . ':update');
        $group->delete('/node', App\Controllers\Admin\NodeController::class . ':delete');
        $group->post('/node/ajax', App\Controllers\Admin\NodeController::class . ':ajax');

        // Ticket Mange
        $group->get('/ticket', App\Controllers\Admin\TicketController::class . ':index');
        $group->post('/ticket', App\Controllers\Admin\TicketController::class . ':add');
        $group->get('/ticket/{id}/view', App\Controllers\Admin\TicketController::class . ':show');
        $group->put('/ticket/{id}', App\Controllers\Admin\TicketController::class . ':update');
        $group->post('/ticket/ajax', App\Controllers\Admin\TicketController::class . ':ajax');

        // Shop Mange
        $group->get('/shop', App\Controllers\Admin\ShopController::class . ':index');
        $group->post('/shop/ajax', App\Controllers\Admin\ShopController::class . ':ajax_shop');
        $group->get('/shop/create', App\Controllers\Admin\ShopController::class . ':create');
        $group->post('/shop', App\Controllers\Admin\ShopController::class . ':add');
        $group->get('/shop/{id}/edit', App\Controllers\Admin\ShopController::class . ':edit');
        $group->put('/shop/{id}', App\Controllers\Admin\ShopController::class . ':update');
        $group->delete('/shop', App\Controllers\Admin\ShopController::class . ':deleteGet');

        // Bought Mange
        $group->get('/bought', App\Controllers\Admin\ShopController::class . ':bought');
        $group->delete('/bought', App\Controllers\Admin\ShopController::class . ':deleteBoughtGet');
        $group->post('/bought/ajax', App\Controllers\Admin\ShopController::class . ':ajax_bought');

//        // Product
//        $group->get('/product',                  App\Controllers\Admin\ProductController::class . ':index');
//        $group->get('/product/create',           App\Controllers\Admin\ProductController::class . ':create');
//        $group->post('/product',                 App\Controllers\Admin\ProductController::class . ':save');
//        $group->get('/product/{id}/edit',        App\Controllers\Admin\ProductController::class . ':edit');
//        $group->put('/product/{id}',             App\Controllers\Admin\ProductController::class . ':update');
//        $group->delete('/product/{id}',          App\Controllers\Admin\ProductController::class . ':delete');

        // Ann Mange
        $group->get('/announcement', App\Controllers\Admin\AnnController::class . ':index');
        $group->get('/announcement/create', App\Controllers\Admin\AnnController::class . ':create');
        $group->post('/announcement', App\Controllers\Admin\AnnController::class . ':add');
        $group->get('/announcement/{id}/edit', App\Controllers\Admin\AnnController::class . ':edit');
        $group->put('/announcement/{id}', App\Controllers\Admin\AnnController::class . ':update');
        $group->delete('/announcement', App\Controllers\Admin\AnnController::class . ':delete');
        $group->post('/announcement/ajax', App\Controllers\Admin\AnnController::class . ':ajax');

        // Detect Mange
        $group->get('/detect', App\Controllers\Admin\DetectController::class . ':index');
        $group->get('/detect/create', App\Controllers\Admin\DetectController::class . ':create');
        $group->post('/detect', App\Controllers\Admin\DetectController::class . ':add');
        $group->get('/detect/{id}/edit', App\Controllers\Admin\DetectController::class . ':edit');
        $group->put('/detect/{id}', App\Controllers\Admin\DetectController::class . ':update');
        $group->delete('/detect', App\Controllers\Admin\DetectController::class . ':delete');
        $group->get('/detect/log', App\Controllers\Admin\DetectController::class . ':log');
        $group->post('/detect/ajax', App\Controllers\Admin\DetectController::class . ':ajax_rule');
        $group->post('/detect/log/ajax', App\Controllers\Admin\DetectController::class . ':ajax_log');

        // IP Mange
        $group->get('/block', App\Controllers\Admin\IpController::class . ':block');
        $group->get('/unblock', App\Controllers\Admin\IpController::class . ':unblock');
        $group->post('/unblock', App\Controllers\Admin\IpController::class . ':doUnblock');
        $group->get('/login', App\Controllers\Admin\IpController::class . ':index');
        $group->get('/alive', App\Controllers\Admin\IpController::class . ':alive');
        $group->post('/block/ajax', App\Controllers\Admin\IpController::class . ':ajax_block');
        $group->post('/unblock/ajax', App\Controllers\Admin\IpController::class . ':ajax_unblock');
        $group->post('/login/ajax', App\Controllers\Admin\IpController::class . ':ajax_login');
        $group->post('/alive/ajax', App\Controllers\Admin\IpController::class . ':ajax_alive');

        // Code Mange
        $group->get('/code', App\Controllers\Admin\CodeController::class . ':index');
        $group->get('/code/create', App\Controllers\Admin\CodeController::class . ':create');
        $group->post('/code', App\Controllers\Admin\CodeController::class . ':add');
        $group->get('/donate/create', App\Controllers\Admin\CodeController::class . ':donate_create');
        $group->post('/donate', App\Controllers\Admin\CodeController::class . ':donate_add');
        $group->post('/code/ajax', App\Controllers\Admin\CodeController::class . ':ajax_code');

        // User Mange
        $group->get('/user', App\Controllers\Admin\UserController::class . ':index');
        $group->get('/user/{id}/edit', App\Controllers\Admin\UserController::class . ':edit');
        $group->put('/user/{id}', App\Controllers\Admin\UserController::class . ':update');
        $group->delete('/user', App\Controllers\Admin\UserController::class . ':delete');
        $group->post('/user/changetouser', App\Controllers\Admin\UserController::class . ':changetouser');
        $group->post('/user/ajax', App\Controllers\Admin\UserController::class . ':ajax');
        $group->post('/user/create', App\Controllers\Admin\UserController::class . ':createNewUser');

        // Coupon Mange
        $group->get('/coupon', App\Controllers\AdminController::class . ':coupon');
        $group->post('/coupon', App\Controllers\AdminController::class . ':addCoupon');
        $group->post('/coupon/ajax', App\Controllers\AdminController::class . ':ajax_coupon');

        // Subscribe Log Mange
        $group->get('/subscribe', App\Controllers\Admin\SubscribeLogController::class . ':index');
        $group->post('/subscribe/ajax', App\Controllers\Admin\SubscribeLogController::class . ':ajax_subscribe_log');

        // Detect Ban Mange
        $group->get('/detect/ban', App\Controllers\Admin\DetectBanLogController::class . ':index');
        $group->post('/detect/ban/ajax', App\Controllers\Admin\DetectBanLogController::class . ':ajax_log');

        // 指定用户购买记录以及添加套餐
        $group->get('/user/{id}/bought', App\Controllers\Admin\UserLog\BoughtLogController::class . ':bought');
        $group->post('/user/{id}/bought/ajax', App\Controllers\Admin\UserLog\BoughtLogController::class . ':bought_ajax');
        $group->delete('/user/bought', App\Controllers\Admin\UserLog\BoughtLogController::class . ':bought_delete');
        $group->post('/user/{id}/bought/buy', App\Controllers\Admin\UserLog\BoughtLogController::class . ':bought_add');

        // 指定用户充值记录
        $group->get('/user/{id}/code', App\Controllers\Admin\UserLog\CodeLogController::class . ':index');
        $group->post('/user/{id}/code/ajax', App\Controllers\Admin\UserLog\CodeLogController::class . ':ajax');

        // 指定用户订阅记录
        $group->get('/user/{id}/sublog', App\Controllers\Admin\UserLog\SubLogController::class . ':index');
        $group->post('/user/{id}/sublog/ajax', App\Controllers\Admin\UserLog\SubLogController::class . ':ajax');

        // 指定用户审计记录
        $group->get('/user/{id}/detect', App\Controllers\Admin\UserLog\DetectLogController::class . ':index');
        $group->post('/user/{id}/detect/ajax', App\Controllers\Admin\UserLog\DetectLogController::class . ':ajax');

        // 指定用户登录记录
        $group->get('/user/{id}/login', App\Controllers\Admin\UserLog\LoginLogController::class . ':index');
        $group->post('/user/{id}/login/ajax', App\Controllers\Admin\UserLog\LoginLogController::class . ':ajax');

        // 设置中心
        $group->get('/setting', App\Controllers\Admin\SettingController::class . ':index');
        $group->post('/setting', App\Controllers\Admin\SettingController::class . ':save');
        $group->post('/setting/email', App\Controllers\Admin\SettingController::class . ':test');
        $group->post('/setting/payment', App\Controllers\Admin\SettingController::class . ':payment');

        // Config Mange
        $group->group('/config', static function (RouteCollectorProxy $group): void {
            $group->put('/update/{key}', App\Controllers\Admin\GConfigController::class . ':update');
            $group->get('/update/{key}/edit', App\Controllers\Admin\GConfigController::class . ':edit');

            $group->get('/telegram', App\Controllers\Admin\GConfigController::class . ':telegram');
            $group->post('/telegram/ajax', App\Controllers\Admin\GConfigController::class . ':telegram_ajax');
        });
    })->add(new Admin());

    if ($_ENV['enableAdminApi']){
        $app->group('/admin/api', static function (RouteCollectorProxy $group): void {
            $group->get('/nodes', App\Controllers\Admin\ApiController::class . ':getNodeList');
            $group->get('/node/{id}', App\Controllers\Admin\ApiController::class . ':getNodeInfo');
            $group->get('/ping', App\Controllers\Admin\ApiController::class . ':ping');

            // Re-bind controller, bypass admin token require
            $group->post('/node', App\Controllers\Admin\NodeController::class . ':add');
            $group->put('/node/{id}', App\Controllers\Admin\NodeController::class . ':update');
            $group->delete('/node', App\Controllers\Admin\NodeController::class . ':delete');
        })->add(new AuthorizationBearer($_ENV['adminApiToken']));
    }

    // mu
    $app->group('/mod_mu', static function (RouteCollectorProxy $group): void {
        // 流媒体检测
        $group->post('/media/saveReport', App\Controllers\Mod_Mu\NodeController::class . ':saveReport');
        // 其他
        $group->get('/nodes/{id}/info', App\Controllers\Mod_Mu\NodeController::class . ':get_info');
        $group->post('/nodes/{id}/info', App\Controllers\Mod_Mu\NodeController::class . ':info');
        $group->get('/nodes', App\Controllers\Mod_Mu\NodeController::class . ':get_all_info');
        $group->post('/nodes/config', App\Controllers\Mod_Mu\NodeController::class . ':getConfig');

        $group->get('/users', App\Controllers\Mod_Mu\UserController::class . ':index');
        $group->post('/users/traffic', App\Controllers\Mod_Mu\UserController::class . ':addTraffic');
        $group->post('/users/aliveip', App\Controllers\Mod_Mu\UserController::class . ':addAliveIp');
        $group->post('/users/detectlog', App\Controllers\Mod_Mu\UserController::class . ':addDetectLog');

        $group->get('/func/detect_rules', App\Controllers\Mod_Mu\FuncController::class . ':get_detect_logs');
        $group->post('/func/block_ip', App\Controllers\Mod_Mu\FuncController::class . ':addBlockIp');
        $group->get('/func/block_ip', App\Controllers\Mod_Mu\FuncController::class . ':get_blockip');
        $group->get('/func/unblock_ip', App\Controllers\Mod_Mu\FuncController::class . ':get_unblockip');
        $group->get('/func/ping', App\Controllers\Mod_Mu\FuncController::class . ':ping');
        //============================================
    })->add(new Mod_Mu());

    $app->group('/link', static function (RouteCollectorProxy $group): void {
        $group->get('/{token}', App\Controllers\LinkController::class . ':GetContent');
    });

    //通用訂閲
    $app->group('/sub', static function (RouteCollectorProxy $group): void {
        $group->get('/{token}/{subtype}', App\Controllers\SubController::class . ':getContent');
    });

    $app->group('/getClient', static function (RouteCollectorProxy $group): void {
        $group->get('/{token}', App\Controllers\UserController::class . ':getClientfromToken');
    });
};
