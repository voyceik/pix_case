<?php
declare(strict_types=1);

namespace App\Resource;

class WithdrawResource
{
    public static function make(array $data): array
    {
        return [
            'withdrawal_id' => $data['withdrawal_id'] ?? null,
            'amount' => (float) $data['amount'],
            'schedule' => $data['schedule'] ?? null,
        ];
    }
}
