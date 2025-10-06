<?php
declare(strict_types=1);

namespace App\Repositories\Contracts;

interface IdempotencyRepository
{
    public function find(string $key): ?array;
    public function insertIgnore(string $key, string $accountId, string $signature): bool;
    public function link(string $key, string $withdrawalId): void;
}
