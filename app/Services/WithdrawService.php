<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\WithdrawalRepository;
use App\Repositories\Contracts\AccountRepository;
use App\Repositories\Contracts\PixRepository;
use Hyperf\DbConnection\Db;
use DomainException;
use App\Services\MailService;

use function Hyperf\Support\env;

class WithdrawService
{
    public function __construct(
        private WithdrawalRepository $withdrawals,
        private AccountRepository $accounts,
        private MailService $mail,
        private PixRepository $pix
    ) {}

    public function balance(string $accountId): array
    {
        $acc = $this->accounts->findById($accountId);
        if (!$acc) throw new DomainException('Conta não encontrada.');
        return ['accountId' => $accountId, 'balance' => (float)$acc['balance']];
    }

    public function list(string $accountId, string $status = ''): array
    {     
        if (!$this->accounts->findById($accountId)) throw new DomainException('Conta não encontrada.');
        return $this->withdrawals->listByAccount($accountId, $status);
    }

    public function create(array $dto): array
    {
        $scheduled = !empty($dto['schedule']);
        $withdrawalId = $this->withdrawals->create($dto);

        if (!$scheduled) {
            Db::beginTransaction();
            try {
                $acc = $this->accounts->findForUpdate($dto['account_id']);
                if (!$acc) throw new DomainException('Conta não encontrada.');

                $balance = (float) $acc['balance'];
                $amount = (float) $dto['amount'];
                if ($balance < $amount) {
                    $this->withdrawals->finishWithError($withdrawalId, '_nofunds_');
                    Db::commit();
                    throw new DomainException('Sem saldo para a operação de saque.');
                }

                $this->accounts->debit($dto['account_id'], $amount);
                $this->withdrawals->finishSuccess($withdrawalId);
                Db::commit();

            } catch (\Throwable $e) {
                Db::rollBack();
                throw $e;
            }
        }
        else {
            throw new DomainException('Saque imediato não pode ter data de agendamento.');
        }
        
        $when = new \DateTimeImmutable('now', new \DateTimeZone(env('TZ', 'America/Sao_Paulo')));
        $this->mail->queueWithdrawReceipt($dto['pix']['key'], (float) $dto['amount'], $dto['pix'], $when, $withdrawalId);

        return ['status' => 200, 'message' => 'Saque PIX confirmado ('.$withdrawalId.')'];
    }
}
