<?php
declare(strict_types=1);

namespace App\Services;

use FriendsOfHyperf\Mail\Facade\Mail;
use App\Job\SendMailJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Psr\Log\LoggerInterface;

use function Hyperf\Support\env;

class MailService
{
    private DriverInterface $queue;

    public function __construct(
        DriverFactory $factory,
        private LoggerInterface $logger
    ) {
        // pega o driver da fila "default"
        $this->queue = $factory->get('default');
    }

    public function queueWithdrawReceipt(
        string $toEmail,
        float $amount,
        array $pix,
        \DateTimeInterface $when,
        string $withdrawalId
    ): void {
        try {
            $job = new SendMailJob(
                $toEmail,
                (float) $amount,
                $pix,
                $when->format('Y-m-d H:i:s'),
                $withdrawalId
            );
            $this->queue->push($job);
        } catch (\Throwable $e) {
            $this->logger->warning('Falha ao enfileirar e-mail', [
                'withdrawalId' => $withdrawalId,
                'error' => $e->getMessage(),
            ]); 
        } 
    }
                    
    public function sendScheduledReceipt(
        string $toEmail,
        float $amount,
        array $pix,
        \DateTimeInterface $when,
        string $withdrawalId,
        bool $canceled
    ): void {
        try {
            $subject = sprintf(($canceled ? 'Cancelado ' : '') . 'Saque PIX agendado (%s)', $withdrawalId);
            $body = sprintf(
                ($canceled ? 'Cancelado ' : '') . "Saque agendado para %s\nValor: R$ %.2f\nPIX: %s (%s)\n",
                $when->format('Y-m-d H:i:s'),
                $amount,
                $pix['key'] ?? '-',
                $pix['type'] ?? '-'
            );

            Mail::mailer(env('MAIL_MAILER','smtp'))->raw($body, function ($m) use ($toEmail, $subject) {
                $m->to($toEmail);
                $m->subject($subject);
            });
        } catch (\Throwable $e) {
            $this->logger->warning('Falha ao enviar e-mail', [
                'withdrawalId' => $withdrawalId,
                'error' => $e->getMessage(),
            ]); 
        } 
    }    
}
