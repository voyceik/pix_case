<?php
declare(strict_types=1);

use Hyperf\AsyncQueue\Driver\RedisDriver;

return [
    'default' => [
        'driver' => RedisDriver::class,
        'redis' => [
            'pool' => 'default',
        ],
        'channel' => 'default',      
        'timeout' => 2,              
        'retry_seconds' => 5,       
        'handle_timeout' => 15,      
        'processes' => 1,           
        'concurrent' => [
            'limit' => 20,           
        ],
        'automatic_destruct' => false,
    ],
];
