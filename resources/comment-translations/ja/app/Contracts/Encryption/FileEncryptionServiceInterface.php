<?php

return [
    'File encryption service interface' => 'ファイル暗号化サービスインターフェース',
    'Used for backup encryption, attachment protection, etc.' => 'バックアップ暗号化、添付ファイル保護等に使用します。',
    'Encrypt a file' => 'ファイルを暗号化',
    'Path to the file to encrypt' => '暗号化するファイルのパス',
    'Output path for the encrypted file' => '暗号化されたファイルの出力先パス',
    'Encryption key (uses APP_KEY if null)' => '暗号化キー（null の場合は APP_KEY を使用）',
    'Decrypt a file' => 'ファイルを復号',
    'Path to the encrypted file' => '暗号化されたファイルのパス',
    'Output path for the decrypted file' => '復号されたファイルの出力先パス',
    'Decryption key (uses APP_KEY if null)' => '復号キー（null の場合は APP_KEY を使用）',
    'Path to the decrypted file' => '復号されたファイルのパス',
    'If decryption fails' => '復号に失敗した場合',
    'Get the encryption algorithm identifier' => '暗号化アルゴリズムの識別子を取得',
    'Generate a new encryption key' => '新しい暗号化キーを生成',
    'Determine if a file is encrypted by checking the magic bytes in the header' => 'ファイルが暗号化されているかをヘッダーのマジックバイトで判定',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'File encryption service interface' => 'machine',
        'Used for backup encryption, attachment protection, etc.' => 'machine',
        'Encrypt a file' => 'machine',
        'Path to the file to encrypt' => 'machine',
        'Output path for the encrypted file' => 'machine',
        'Encryption key (uses APP_KEY if null)' => 'machine',
        'Decrypt a file' => 'machine',
        'Path to the encrypted file' => 'machine',
        'Output path for the decrypted file' => 'machine',
        'Decryption key (uses APP_KEY if null)' => 'machine',
        'Path to the decrypted file' => 'machine',
        'If decryption fails' => 'machine',
        'Get the encryption algorithm identifier' => 'machine',
        'Generate a new encryption key' => 'machine',
        'Determine if a file is encrypted by checking the magic bytes in the header' => 'machine',
    ],
];
