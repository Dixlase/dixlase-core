<?php

return [
    'Suppress re-notification of the same version: only notify when last_notified_version != available_version' => '同一バージョンの再通知抑制：last_notified_version != available_version のものだけ通知',
    'Send email notification to administrator about newly found updates and advance last_notified_version' => '新たに見つかった更新を管理者にメール通知し、last_notified_version を進める。',
    'Update last_notified_version only for those successfully notified' => '通知に成功したものだけ last_notified_version を更新',
    'Extract only those where available_version differs from last_notified_version' => 'available_version が last_notified_version と異なるものだけ抽出する。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Suppress re-notification of the same version: only notify when last_notified_version != available_version' => 'machine',
        'Send email notification to administrator about newly found updates and advance last_notified_version' => 'machine',
        'Update last_notified_version only for those successfully notified' => 'machine',
        'Extract only those where available_version differs from last_notified_version' => 'machine',
    ],
];
