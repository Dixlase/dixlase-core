<?php

return [
    'Signature verification contract' => '署名検証コントラクト',
    'Provides signature verification abstraction on the Core side.' => 'コア側で署名検証の抽象化を提供します。',
    'When the DixlaseDevKit plugin is installed, performs Ed25519-based' => 'DixlaseDevKit プラグインがインストール済みの場合は Ed25519 ベースの',
    'verification; when not installed, the stub implementation returns unsigned.' => '検証を実行し、未インストール時はスタブ実装が unsigned を返します。',
    'Verify plugin signature' => 'プラグインの署名を検証する',
    'Plugin slug (kebab-case)' => 'プラグインのスラッグ（kebab-case）',
    'Verification result' => '検証結果',
    'Whether signature verification is available' => '署名検証が利用可能かどうか',
    'Returns false when the DixlaseDevKit plugin is not installed.' => 'DixlaseDevKit プラグインがインストールされていない場合は false を返します。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Signature verification contract' => 'machine',
        'Provides signature verification abstraction on the Core side.' => 'machine',
        'When the DixlaseDevKit plugin is installed, performs Ed25519-based' => 'machine',
        'verification; when not installed, the stub implementation returns unsigned.' => 'machine',
        'Verify plugin signature' => 'machine',
        'Plugin slug (kebab-case)' => 'machine',
        'Verification result' => 'machine',
        'Whether signature verification is available' => 'machine',
        'Returns false when the DixlaseDevKit plugin is not installed.' => 'machine',
    ],
];
