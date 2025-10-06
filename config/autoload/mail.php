<?php
declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'default' => env('MAIL_MAILER', 'smtp'), 
    'mailers' => [
        'smtp' => [       
            'scheme'     => 'smtp',
            'transport'  => 'smtp',
            'host'       => env('MAIL_HOST', '127.0.0.1'),
            'port'       => (int) env('MAIL_PORT', 25),
            'encryption' => env('MAIL_ENCRYPTION'), // vazio p/ Mailpit 1025
            'username'   => env('MAIL_USERNAME', ''),
            'password'   => env('MAIL_PASSWORD', ''),
            'timeout'    => null,
            'auth_mode'  => null,
        ],
        'log' => ['transport' => 'log'],
        'array' => ['transport' => 'array'],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'name'    => env('MAIL_FROM_NAME', 'TecnoFit PIX'),
    ],
];
