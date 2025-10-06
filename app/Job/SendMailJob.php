<?php
declare(strict_types=1);

namespace App\Job;

use FriendsOfHyperf\Mail\Facade\Mail;
use Hyperf\AsyncQueue\Job;

use function Hyperf\Support\env;

use Psr\Log\LoggerInterface;
use Hyperf\Context\ApplicationContext;

class SendMailJob extends Job
{
    public function __construct(
        private string $toEmail,
        private float $amount,
        private array $pix,
        private string $when,          // 'Y-m-d H:i:s'
        private string $withdrawalId
    ) {}

    public function handle(): void
    {
        $logger = ApplicationContext::getContainer()->get(LoggerInterface::class);
        $this->trace("START job for {$this->toEmail}, amount={$this->amount}, id={$this->withdrawalId}");
        $logger->info('SendEmailJob START', [
            'to' => $this->toEmail,
            'amount' => $this->amount,
            'withdrawalId' => $this->withdrawalId,
        ]);

         try {
            $subject = sprintf('Saque PIX confirmado (%s)', $this->withdrawalId);
            $body = sprintf(
                "Saque efetuado em %s\nValor: R$ %.2f\nPIX: %s (%s)\n",
                $this->when,
                $this->amount,
                $this->pix['key'] ?? '-',
                $this->pix['type'] ?? '-'
            );

            Mail::mailer(env('MAIL_MAILER','smtp'))->raw($body, function ($m) use ($subject) {
                $m->to($this->toEmail);
                $m->subject($subject);
            });

            $this->trace("DONE job id={$this->withdrawalId}");
            $logger->info('SendEmailJob DONE', ['withdrawalId' => $this->withdrawalId]);
        } catch (\Throwable $e) {
            $this->trace("ERROR job id={$this->withdrawalId} :: {$e->getMessage()}\n{$e->getTraceAsString()}");
            $logger->error('SendEmailJob ERROR', [
                'withdrawalId' => $this->withdrawalId,
                'error' => $e->getMessage(),
            ]);

            // rethrow para permitir retry da fila
            throw $e;
        }
    }

    private function trace(string $line): void
    {
        @file_put_contents(BASE_PATH . '/runtime/mail_job.log',
            '[' . date('Y-m-d H:i:s') . "] $line\n",
            FILE_APPEND | LOCK_EX
        );
    }

    // (opcional) número de tentativas antes de falhar de vez (default 3)
    public function attempts(): int
    {
        return 5;
    }

    // (opcional) atraso entre tentativas subsequentes em segundos
    public function retryAfter(): int
    {
        return 15;
    }
}
