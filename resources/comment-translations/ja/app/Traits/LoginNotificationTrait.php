<?php

return [
    'Common trait for login notifications' => 'ログイン通知の共通トレイト',
    'Provides functionality commonly used in login notification processing for members and users.' => 'メンバーとユーザーのログイン通知処理で共通して使用される機能を提供します。',
    'Service classes using this trait must implement the following abstract methods.' => 'このトレイトを使用するサービスクラスは、以下の抽象メソッドを実装する必要があります。',
    'Retrieve global settings key name (implement in child class)' => 'グローバル設定のキー名を取得（継承先で実装）',
    'Settings key name (e.g. \'login_notification_mode\')' => '設定キー名（例: \'login_notification_mode\'）',
    'Retrieve function to retrieve settings value (implement in child class)' => '設定値を取得する関数を取得（継承先で実装）',
    'Settings retrieval function' => '設定取得関数',
    'Retrieve notification class name (implement in child class)' => '通知クラス名を取得（継承先で実装）',
    'Notification class name' => '通知クラス名',
    'Retrieve log context name (implement in child class)' => 'ログコンテキスト名を取得（継承先で実装）',
    'Context name (e.g. \'Admin login notification\', \'User login notification\')' => 'コンテキスト名（例: \'Admin login notification\', \'User login notification\'）',
    'Process login notification' => 'ログイン通知を処理',
    'User model (Member or User)' => 'ユーザーモデル（Member または User）',
    'Request' => 'リクエスト',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Common trait for login notifications' => 'machine',
        'Provides functionality commonly used in login notification processing for members and users.' => 'machine',
        'Service classes using this trait must implement the following abstract methods.' => 'machine',
        'Retrieve global settings key name (implement in child class)' => 'machine',
        'Settings key name (e.g. \'login_notification_mode\')' => 'machine',
        'Retrieve function to retrieve settings value (implement in child class)' => 'machine',
        'Settings retrieval function' => 'machine',
        'Retrieve notification class name (implement in child class)' => 'machine',
        'Notification class name' => 'machine',
        'Retrieve log context name (implement in child class)' => 'machine',
        'Context name (e.g. \'Admin login notification\', \'User login notification\')' => 'machine',
        'Process login notification' => 'machine',
        'User model (Member or User)' => 'machine',
        'Request' => 'machine',
    ],
];
