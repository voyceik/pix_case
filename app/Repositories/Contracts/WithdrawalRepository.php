<?php
declare(strict_types=1);

namespace App\Repositories\Contracts;

interface WithdrawalRepository
{
    public function create(array $dto): string;
    public function finishSuccess(string $withdrawalId): void;
    public function finishWithError(string $withdrawalId, string $reason): void;
    public function listByAccount(string $accountId, string $status = ''): array;
    public function cancelIfPending(string $withdrawalId, string $accountId): bool;
}
