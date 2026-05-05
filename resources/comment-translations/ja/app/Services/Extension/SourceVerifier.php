<?php

return [
    '@internal For Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',

    // SourceVerifier class docblock
    'Verifies the authenticity of extension sources using Ed25519 signatures.' => 'Ed25519 署名を使って拡張機能ソースの真正性を検証します。',
    'Official sources are signed with the configured authority key (managed' => '公式ソースは設定された authority key (authority / 鍵管理プラグインが管理)',
    'by an authority/key-management plugin); this class provides the' => 'で署名されています。このクラスはコアスタブとして検証ロジックを',
    'verification logic as a core stub. When DixlaseDevKit is installed,' => '提供します。DixlaseDevKit がインストールされている場合は、',
    'the actual Ed25519 verification is performed; otherwise, the signature' => '実際の Ed25519 検証が実行されます。インストールされていない場合は、',
    'status is returned as pending.' => '署名ステータスは pending として返されます。',

    // resolvePublicKey() docblock
    'Looks up the public key by the configured key ID. Currently uses' => '設定された key ID で公開鍵を検索します。現在は環境変数を',
    'an environment variable; future versions will integrate with a' => '使用していますが、将来のバージョンでは集中管理のために',
    'key-vault plugin for centralised key management.' => 'key-vault プラグインと統合する予定です。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal For Core use only. Do not reference from plugins/themes' => 'machine',
        'Verifies the authenticity of extension sources using Ed25519 signatures.' => 'human',
        'Official sources are signed with the configured authority key (managed' => 'human',
        'by an authority/key-management plugin); this class provides the' => 'human',
        'verification logic as a core stub. When DixlaseDevKit is installed,' => 'human',
        'the actual Ed25519 verification is performed; otherwise, the signature' => 'human',
        'status is returned as pending.' => 'human',
        'Looks up the public key by the configured key ID. Currently uses' => 'human',
        'an environment variable; future versions will integrate with a' => 'human',
        'key-vault plugin for centralised key management.' => 'human',
    ],
];
