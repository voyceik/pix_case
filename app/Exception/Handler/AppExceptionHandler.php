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

namespace App\Exception\Handler;

use DomainException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation(); // não deixa outro handler engolir a resposta

        // Define status
        $status = 500;
        if ($throwable instanceof DomainException) {
            $status = $throwable->getCode() >= 400 ? (int) $throwable->getCode() : 400;
        }

        $payload = [
            'status'  => $status,
            'message' => $throwable->getMessage() ?: 'Erro interno',
        ];

        return $response
            ->withStatus($status)
            ->withAddedHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream(json_encode($payload, JSON_UNESCAPED_UNICODE)));
    }

    public function isValid(Throwable $throwable): bool
    {
        // Trata DomainException e qualquer outra Throwable
        return true;
    }
}