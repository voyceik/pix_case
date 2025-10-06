<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;
use App\Controller\AccountWithdrawController;

Router::get('/', function () {
    return ['status' => '200', 'message' => 'Serviço ok!'];
});

Router::get('/favicon.ico', function () {
    return '';
});
