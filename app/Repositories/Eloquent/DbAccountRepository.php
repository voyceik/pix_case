<?php
declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AccountRepository;
use Hyperf\DbConnection\Db;

class DbAccountRepository implements AccountRepository
{
    private const T_ACCOUNT = 'account';

    public function findById(string $id): ?array
    {
        $row = Db::table(self::T_ACCOUNT)->where('id', $id)->first();
        return $row ? (array) $row : null;
    }

    public function findForUpdate(string $id): ?array
    {
        $row = Db::table(self::T_ACCOUNT)->where('id', $id)->lockForUpdate()->first();
        return $row ? (array) $row : null;
    }

    public function debit(string $id, float $amount): void
    {
        Db::table(self::T_ACCOUNT)->where('id', $id)->decrement('balance', $amount);
    }
}
