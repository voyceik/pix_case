<?php
declare(strict_types=1);

use App\Repositories\Contracts\WithdrawalRepository;
use App\Repositories\Contracts\AccountRepository;
use App\Repositories\Contracts\PixRepository;
use App\Repositories\Contracts\IdempotencyRepository;

use App\Repositories\Eloquent\DbWithdrawalRepository;
use App\Repositories\Eloquent\DbAccountRepository;
use App\Repositories\Eloquent\DbPixRepository;
use App\Repositories\Eloquent\DbIdempotencyRepository;

use App\Services\WithdrawService;
use App\Services\ScheduledWithdrawService;
use App\Services\IdempotencyService;

return [
    WithdrawalRepository::class  => DbWithdrawalRepository::class,
    AccountRepository::class     => DbAccountRepository::class,
    PixRepository::class         => DbPixRepository::class,
    IdempotencyRepository::class => DbIdempotencyRepository::class,

    WithdrawService::class           => WithdrawService::class,
    ScheduledWithdrawService::class  => ScheduledWithdrawService::class,
    IdempotencyService::class        => IdempotencyService::class,
];
