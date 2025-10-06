<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\IdempotencyRepository;

class IdempotencyService
{
    public function __construct(private IdempotencyRepository $repo) {}

    public function signature(string $path, array $body): string
    {
        return hash('sha256', $path . '|' . json_encode($body));
    }

    /** @return array{status:string, withdrawal_id:?string} */
    public function reserve(string $key, string $accountId, string $signature): array
    {
        $found = $this->repo->find($key);
        if ($found) {
            return ['status' => 'existing', 'withdrawal_id' => $found['withdrawal_id'] ?? null];
        }
        $inserted = $this->repo->insertIgnore($key, $accountId, $signature);
        if (!$inserted) {
            $found = $this->repo->find($key);
            return ['status' => 'existing', 'withdrawal_id' => $found['withdrawal_id'] ?? null];
        }
        return ['status' => 'reserved', 'withdrawal_id' => null];
    }

    public function link(string $key, string $withdrawalId): void
    {
        $this->repo->link($key, $withdrawalId);
    }
}
