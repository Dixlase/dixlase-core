<?php

return [
    '|--------------------------------------------------------------------------' => '|--------------------------------------------------------------------------',
    '|' => '|',
    '| URL of the official Dixlase key management site. Used to retrieve the public' => '| Dixlase 公式の鍵管理サイトの URL。プラグイン署名の公開鍵を取得するために',
    '| key for plugin signatures. In production, specify https://keys.dixlase.com (default).' => '| 使用する。本番では https://keys.dixlase.com を指定（既定）。',
    '| Override via env only when using a different Authority in development environments.' => '| 開発環境で別の Authority を使う場合のみ env で上書きする。',
    '| Cache validity period (hours)' => '| キャッシュ有効期間（時間）',
    '| How long to trust public keys cached in the local DB.' => '| ローカル DB にキャッシュした公開鍵をどれくらい信頼するか。',
    '| Keys older than this period will attempt a re-fetch on the next verification (will' => '| この時間を超えた鍵は次回検証時に再フェッチを試みる（失敗しても古い',
    '| continue with old cache if the fetch fails).' => '| キャッシュで続行する）。',
    '| Fetch timeout (seconds)' => '| フェッチタイムアウト（秒）',
    '| HTTP timeout for retrieving public keys. Too short will fail on network delays,' => '| 公開鍵取得の HTTP タイムアウト。短すぎるとネットワーク遅延で失敗、',
    '| too long will delay plugin installation.' => '| 長すぎるとプラグインインストールが遅延する。',
    '| SSL certificate verification' => '| SSL 証明書検証',
    '| Set to false only when using an Authority with self-signed cert in sandbox or' => '| サンドボックス・社内検証で self-signed cert の Authority を使う場合のみ',
    '| internal verification. In production, must always be true (with CA verification).' => '| false にする。本番では必ず true（CA 検証あり）。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '|--------------------------------------------------------------------------' => 'machine',
        '|' => 'machine',
        '| URL of the official Dixlase key management site. Used to retrieve the public' => 'machine',
        '| key for plugin signatures. In production, specify https://keys.dixlase.com (default).' => 'machine',
        '| Override via env only when using a different Authority in development environments.' => 'machine',
        '| Cache validity period (hours)' => 'machine',
        '| How long to trust public keys cached in the local DB.' => 'machine',
        '| Keys older than this period will attempt a re-fetch on the next verification (will' => 'machine',
        '| continue with old cache if the fetch fails).' => 'machine',
        '| Fetch timeout (seconds)' => 'machine',
        '| HTTP timeout for retrieving public keys. Too short will fail on network delays,' => 'machine',
        '| too long will delay plugin installation.' => 'machine',
        '| SSL certificate verification' => 'machine',
        '| Set to false only when using an Authority with self-signed cert in sandbox or' => 'machine',
        '| internal verification. In production, must always be true (with CA verification).' => 'machine',
    ],
];
