<?php

return [
    'mailers' => [
        'smtp' => 'SMTP（標準）',
        'sendmail' => 'Sendmail',
        'log' => 'Log（ログ出力）',
        'array' => 'Array（配列保存）',
        'failover' => 'Failover（冗長化）',
        'mailgun' => 'Mailgun（外部）',
        'ses' => 'Amazon SES',
        'postmark' => 'Postmark',
    ],
    'encryptions' => [
        '' => 'なし',
        'tls' => 'TLS（推奨）',
        'ssl' => 'SSL',
    ],
];
