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

use App\Exception\Handler\AppExceptionHandler;

use Hyperf\ExceptionHandler\Handler\WhoopsExceptionHandler;
use Hyperf\ExceptionHandler\Handler\PrettyPageExceptionHandler;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;

return [
    'handler' => [
        'http' => [
            AppExceptionHandler::class,
            Hyperf\Validation\Middleware\ValidationExceptionHandler::class
                ?? Hyperf\Validation\Exception\Handler\ValidationExceptionHandler::class,
            Hyperf\ExceptionHandler\Handler\HttpExceptionHandler::class,
            Hyperf\ExceptionHandler\Handler\WhoopsExceptionHandler::class, // --dev
        ],
    ],
];
