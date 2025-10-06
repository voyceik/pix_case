<?php
declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\PixRepository;
use Hyperf\DbConnection\Db;

class DbPixRepository implements PixRepository
{
    private const T_WITHDRAW_PIX = 'account_withdraw_pix';

    public function attachToWithdrawal(string $withdrawalId, array $pix): void
    {
        $now = date('Y-m-d H:i:s');
        Db::table(self::T_WITHDRAW_PIX)->insert([
            'id' => bin2hex(random_bytes(16)),
            'account_withdraw_id' => $withdrawalId,
            'type' => $pix['type'] ?? 'email',
            'key' => $pix['key'] ?? '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
