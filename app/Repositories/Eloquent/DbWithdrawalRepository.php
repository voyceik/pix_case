<?php
declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\WithdrawalRepository;
use Hyperf\DbConnection\Db;

class DbWithdrawalRepository implements WithdrawalRepository
{
    private const T_WITHDRAW = 'account_withdraw';
    private const T_WITHDRAW_PIX = 'account_withdraw_pix';

    public function create(array $dto): string
    {
        $id = $dto['id'] ?? bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');
        Db::table(self::T_WITHDRAW)->insert([
            'id' => $id,
            'account_id' => $dto['account_id'],
            'method' => $dto['method'],
            'amount' => $dto['amount'],
            'scheduled' => !empty($dto['schedule']),
            'scheduled_for' => $dto['schedule'] ?? null,
            'done' => false,
            'error' => false,
            'error_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (($dto['method'] ?? null) === 'PIX' && !empty($dto['pix'])) {
            Db::table(self::T_WITHDRAW_PIX)->insert([
                'id' => bin2hex(random_bytes(16)),
                'account_withdraw_id' => $id,
                'type' => $dto['pix']['type'] ?? 'email',
                'key' => $dto['pix']['key'] ?? '',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $id;
    }

    public function finishSuccess(string $withdrawalId): void
    {
        Db::table(self::T_WITHDRAW)->where('id', $withdrawalId)->update([
            'done' => true,
            'error' => false,
            'error_reason' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function finishWithError(string $withdrawalId, string $reason): void
    {
        Db::table(self::T_WITHDRAW)->where('id', $withdrawalId)->update([
            'done' => true,
            'error' => true,
            'error_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function listByAccount(string $accountId, string $status = ''): array
    {
        $q = Db::table(self::T_WITHDRAW)->where('account_id', $accountId);
        if ($status === 'scheduled') $q->where('scheduled', true)->where('done', false)->where('error', false);
        if ($status === 'pending')   $q->where('done', false);
        if ($status === 'done')      $q->where('done', true)->where('error', false);
        if ($status === 'error')     $q->where('error', true);
        return $q->orderByDesc('created_at')->limit(200)->get()->toArray();
    }

    public function cancelIfPending(string $withdrawalId, string $accountId): bool
    {
        $row = Db::table(self::T_WITHDRAW)
            ->where('id', $withdrawalId)
            ->where('account_id', $accountId)
            ->where('done', false)
            ->where('error', false)
            ->where('scheduled', true)
            ->lockForUpdate()
            ->first();

        if (!$row) return False;
        if (!$row->scheduled || $row->done) return False;

        Db::table(self::T_WITHDRAW)->where('id', $withdrawalId)->update([
            'done' => true,
            'error' => true,
            'error_reason' => '_cancelled_',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return True;
    }
}
