<?php
declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\HasMany;

class Account extends Model
{
    protected ?string $table = 'account';
    protected string $primaryKey = 'id';

    public bool $incrementing = false;
    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'name',
        'balance',
    ];

    protected array $casts = [
        'balance'   => 'decimal:2',
        'created_at'=> 'datetime',
        'updated_at'=> 'datetime',
    ];

    public function withdrawals(): HasMany
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id');
    }
}