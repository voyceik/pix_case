<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\WithdrawalRepository;
use App\Repositories\Contracts\AccountRepository;
use Hyperf\DbConnection\Db;
use DomainException;
use App\Services\MailService;

use function Hyperf\Support\env;

use Psr\Log\LoggerInterface;
use Hyperf\Context\ApplicationContext;

class ScheduledWithdrawService
{
    private const T_WITHDRAW = 'account_withdraw';
    private const T_WITHDRAW_PIX = 'account_withdraw_pix';

    public function __construct(
        private WithdrawalRepository $withdrawals,
        private AccountRepository $accounts,
        private MailService $mail,
    ) {}

    public function create(array $dto): array
    {
        $scheduled = !empty($dto['schedule']);

        if ($scheduled) {
  
            $when = new \DateTimeImmutable($dto['schedule'], new \DateTimeZone(env('TZ', 'America/Sao_Paulo')));
            $now  = new \DateTimeImmutable('now', new \DateTimeZone(env('TZ', 'America/Sao_Paulo')));
  
            if ($when <= $now) {
                throw new DomainException('O agendamento não pode ser para data/hora pretérita.');
            }
            if ($when > $now->modify('+7 days')) {
                throw new DomainException('O agendamento não pode exceder a 7 dias.');
            }

            Db::beginTransaction();
            try {
                $acc = $this->accounts->findForUpdate($dto['account_id']);
                if (!$acc) throw new DomainException('Conta não encontrada.');
                $withdrawalId = $this->withdrawals->create($dto);
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollBack();
                throw $e;
            }
        }
        else {
            throw new DomainException('Saque agendado deve possuir uma data de agendamento.');
        }

        $this->mail->sendScheduledReceipt($dto['pix']['key'], (float) $dto['amount'], $dto['pix'], $when, $withdrawalId, false);

        return ['status' => 200, 'message' => 'Saque PIX agendado ('.$withdrawalId.')'];
    }

    public function processDue(int $limit = 100): int
    {
        $logger = ApplicationContext::getContainer()->get(LoggerInterface::class);
        $now  = new \DateTimeImmutable('now', new \DateTimeZone(env('TZ', 'America/Sao_Paulo')));
        $logger->info('ProcessDue ', ['started_at' => $now->format('Y-m-d H:i:s')]);

        $rows = Db::table('account_withdraw')
            ->where('scheduled', true)
            ->where('done', false)
            ->where('scheduled_for', '<=', $now->format('Y-m-d H:i:s'))
            ->orderBy('scheduled_for', 'asc')
            ->lock('for update skip locked') 
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($rows as $row) {
            Db::beginTransaction();
            try {
                #$acc = Db::table('account')->where('id', $row->account_id)->lockForUpdate()->first();
                $acc = $this->accounts->findForUpdate($row->account_id);
                if (!$acc) {
                    $this->withdrawals->finishWithError($row->id, '_not_found_');
                    Db::commit();
                    $processed++;
                    continue;
                }

                $withdrawal = Db::table('account_withdraw')
                    ->where('id', $row->id)
                    ->first(); 

                if (!$withdrawal || $withdrawal->done) {
                    Db::commit(); 
                    $processed++;
                    continue; 
                }

                $withdrawalId = $row->id;
                $account_id = $row->account_id;
                $balance = (float) ($acc['balance'] ?? 0);
                $amount  = (float) $row->amount;

                if ($balance < $amount) {
                    $this->withdrawals->finishWithError($withdrawalId, '_nofunds_');
                    Db::commit();
                    $processed++;
                    continue;
                }

                $this->accounts->debit($account_id, $amount);
                $this->withdrawals->finishSuccess($withdrawalId);
                Db::commit();
                $processed++;

                $pix = Db::table('account_withdraw_pix')
                    ->where('account_withdraw_id', $withdrawalId)
                    ->first();

                $when = new \DateTimeImmutable('now', new \DateTimeZone(env('TZ', 'America/Sao_Paulo')));    
                $this->mail->queueWithdrawReceipt((string) $pix->key, $amount, ['type' => $pix->type ?? 'email', 'key' => $pix->key], $when, $withdrawalId);

            } catch (\Throwable $e) {
                Db::rollBack();
                $logger->info('ProcessDue ERROR', ['withdrawId' => $withdrawalId, 'message' => $e->getMessage(), 'acc' => implode(' ', $acc)
            ]);
            }
        }

        $now  = new \DateTimeImmutable('now', new \DateTimeZone(env('TZ', 'America/Sao_Paulo')));
        $logger->info('ProcessDue ', ['finished_at' => $now->format('Y-m-d H:i:s'), 'processed' => $processed]);

        return $processed;
    }

    public function cancel(string $accountId, string $withdrawalId): void
    {
        $dto = null;

        Db::beginTransaction();
        try {
            $data = Db::table(self::T_WITHDRAW)
                ->select([
                    'account_withdraw.amount', 
                    'account_withdraw.scheduled_for', 
                    'account_withdraw_pix.type AS pix_type', 
                    'account_withdraw_pix.key AS pix_key'
                ])
                ->join(self::T_WITHDRAW_PIX, 'account_withdraw_pix.account_withdraw_id', '=', 'account_withdraw.id')
                ->where('account_withdraw.id', $withdrawalId)
                ->where('account_withdraw.account_id', $accountId)
                ->first();

            $ok = $this->withdrawals->cancelIfPending($withdrawalId, $accountId);
            
            if (!$ok || !$data) {
                Db::rollBack();
                throw new \DomainException('Saque agendado não pode ser cancelado.');
            }
            
            $dto = [
                'amount' => $data->amount,
                'scheduled_for' => $data->scheduled_for,
                'pix' => [
                    'type' => $data->pix_type,
                    'key' => $data->pix_key,
                ],
            ];
            
            Db::commit();

            $when = new \DateTimeImmutable($dto['scheduled_for'], new \DateTimeZone(env('TZ', 'America/Sao_Paulo')));

            $this->mail->sendScheduledReceipt(
                $dto['pix']['key'],
                (float) $dto['amount'], 
                $dto['pix'], 
                $when,
                $withdrawalId, 
                true 
            );

        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
