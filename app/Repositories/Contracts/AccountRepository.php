<?php
declare(strict_types=1);

namespace App\Repositories\Contracts;

interface AccountRepository
{
    public function findById(string $id): ?array;
    public function findForUpdate(string $id): ?array;
    public function debit(string $id, float $amount): void;
}
