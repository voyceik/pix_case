<?php
declare(strict_types=1);

namespace App\Controller;

use DomainException;
use App\Request\AccountWithdrawRequest;
use App\Services\WithdrawService;
use App\Services\ScheduledWithdrawService;
use App\Services\IdempotencyService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use OpenApi\Annotations as OA; 

#[Controller(prefix: '/account')]
class AccountWithdrawController
{
    public function __construct(
        private WithdrawService $withdraw,
        private ScheduledWithdrawService $scheduledWithdraw,
        private IdempotencyService $idempotency
    ) {}

    #[RequestMapping(path: '{accountId}/balance/withdraw', methods: 'POST')]
    public function withdraw(string $accountId, AccountWithdrawRequest $request, RequestInterface $http): array
    {
        $dto = $request->validated();
        $dto['account_id'] = $accountId;

        $key = (string) ($http->getHeaderLine('Idempotency-Key') ?: '');
        $sig = $this->idempotency->signature((string)$http->getUri()->getPath(), $dto);

        if ($key) {
            $res = $this->idempotency->reserve($key, $accountId, $sig);
            if ($res['status'] === 'existing' && $res['withdrawal_id']) {
                return ['withdrawal_id' => $res['withdrawal_id'], 'amount' => (float) $dto['amount'], 'schedule' => $dto['schedule'] ?? null];
            }
        }

        $result = empty($dto['schedule'])
            ? $this->withdraw->create($dto)          
            : $this->scheduledWithdraw->create($dto); 

        if ($key) {
            $this->idempotency->link($key, $result['withdrawal_id']);
        }
        return $result;
    }

    #[RequestMapping(path: '{accountId}/balance', methods: 'GET')]
    public function balance(string $accountId): array
    {   
        return $this->withdraw->balance($accountId);
    }

    #[RequestMapping(path: '{accountId}/withdrawals', methods: 'GET')]
    public function list(string $accountId, RequestInterface $request): array
    {
         $status = (string) ($request->query('status') ?? 'ALL'); 
         return $this->withdraw->list($accountId, $status);
    }

    #[RequestMapping(path: '{accountId}/cancel/{withdrawalId}', methods: 'GET')]
    public function cancel(string $accountId, string $withdrawalId): array
    {
        $this->scheduledWithdraw->cancel($accountId, $withdrawalId);
        return ['status' => 200, 'message' => 'Cancelado Saque PIX agendado ('.$withdrawalId.')'];
    }
}
