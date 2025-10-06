<?php
declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\Model;

class RequestIdempotency extends Model
{
    protected ?string $table = 'request_idempotency';
    protected string $primaryKey = 'idempotency_key';

    public bool $incrementing = false;
    protected string $keyType = 'string';

    protected array $fillable = [
        'idempotency_key',
        'account_id',
        'signature',
        'withdrawal_id',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
