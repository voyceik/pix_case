<?php
declare(strict_types=1);

use Hyperf\Crontab\Crontab;
use App\Services\ScheduledWithdrawService;

return [
    'enable' => true, 
    'crontab' => [
        (new Crontab())
            ->setName('finish-pending-withdrawals')
            ->setRule('* * * * *') 
            ->setCallback([ScheduledWithdrawService::class, 'processDue'])
            ->setMemo('Processa saques agendados.')
            #->setOnOneServer(true) 
        ,
    ],
];