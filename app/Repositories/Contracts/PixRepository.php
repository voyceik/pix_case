<?php
declare(strict_types=1);

namespace App\Repositories\Contracts;

interface PixRepository
{
    public function attachToWithdrawal(string $withdrawalId, array $pix): void;
}
