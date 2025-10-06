<?php
declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\IdempotencyRepository;
use Hyperf\DbConnection\Db;

class DbIdempotencyRepository implements IdempotencyRepository
{
    private const TABLE = 'request_idempotency';

    public function find(string $key): ?array
    {
        $row = Db::table(self::TABLE)->where('idempotency_key', $key)->first();
        return $row ? (array) $row : null;
    }

    public function insertIgnore(string $key, string $accountId, string $signature): bool
    {
        $now = date('Y-m-d H:i:s');
        try {
            Db::table(self::TABLE)->insert([
                'idempotency_key' => $key,
                'account_id' => $accountId,
                'signature' => $signature,
                'withdrawal_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return True;
        } catch (\Throwable $e) {
            return False;
        }
    }

    public function link(string $key, string $withdrawalId): void
    {
        Db::table(self::TABLE)->where('idempotency_key', $key)->update([
            'withdrawal_id' => $withdrawalId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
