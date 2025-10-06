<?php
declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\BelongsTo;

class AccountWithdrawPix extends Model
{
    protected ?string $table = 'account_withdraw_pix';
    protected string $primaryKey = 'id';

    public bool $incrementing = false;
    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'account_withdraw_id',
        'type',
        'key',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(AccountWithdraw::class, 'account_withdraw_id', 'id');
    }
}
