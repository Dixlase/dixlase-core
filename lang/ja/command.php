<?php

return [
    "make_plugin" => [
        "enter_plugin_name" => "プラグイン名を入力してください",
        "enter_author_name" => "開発者名を入力してください",
        "enter_website_url" => "開発者のWebサイトURLを入力してください（https://の後の部分のみ入力）",
        "select_license" => "ライセンスを選択してください",
        "enter_license_number" => "ライセンス番号を入力してください（デフォルト：なし）：",
        "confirm_install" => "プラグインをインストールしますか？",
        "confirm_enable" => "プラグインを有効化しますか？",
        "success" => "プラグイン :name が正常に作成されました！",
        "already_exists" => "プラグイン ':name' は既に存在します。",
        "installed" => "プラグイン :name がインストールされました。",
        "enabled" => "プラグイン ':name' が有効化されました。",
        "not_found" => "プラグイン ':name' がデータベースに見つかりません。",
        "no_assets" => "プラグイン ':name' のアセットディレクトリが見つかりません。",
        "files" => [
            "service_provider" => "サービスプロバイダ [:name] がプラグイン [:plugin] 用に作成されました。",
            "controller" => "コントローラ [:name] がプラグイン [:plugin] 用に作成されました。",
            "model" => "モデル [:name] がプラグイン [:plugin] 用に作成されました。",
            "policy" => "ポリシー [:name] がプラグイン [:plugin] 用に作成されました。",
            "listener" => "リスナー [:name] がプラグイン [:plugin] 用に作成されました。",
            "test" => "テスト [:name] がプラグイン [:plugin] 用に作成されました。",
            "migration" => "マイグレーション :name がプラグイン [:plugin] 用に作成されました。",
            "resource" => "リソース [:name] がプラグイン [:plugin] 用に作成されました。",
            "command" => "コマンド [:name] がプラグイン [:plugin] 用に作成されました。",
            "job" => "ジョブ [:name] がプラグイン [:plugin] 用に作成されました。",
            "notification" => "通知 [:name] がプラグイン [:plugin] 用に作成されました。",
            "seeder" => "シーダー :name がプラグイン [:plugin] 用に作成されました。",
            "factory" => "ファクトリ [:name] がプラグイン [:plugin] 用に作成されました。",
            "routes" => "ルートファイルがプラグイン用に作成されました。",
            "config" => "コンフィグファイルがプラグイン用に作成されました。",
            "lang" => "言語ファイル（en & ja）がプラグイン用に作成されました。",
            "vite" => "Vite設定ファイルがプラグイン用に作成されました。",
            "composer" => "Composer.jsonファイルがプラグイン用に作成されました。",
            "readme" => "README.mdがプラグイン用に作成されました。"
        ],
        "license_options" => [
            "gpl" => "GPL-3.0",
            "agpl" => "AGPL-3.0",
            "mit" => "MIT",
            "apache" => "Apache-2.0",
            "bsd3" => "BSD-3-Clause",
            "lgpl" => "LGPL-3.0",
            "commercial" => "Commercial",
            "custom" => "独自ライセンス"
        ]
    ],
    'scope' => [
        'prompt' => 'スコープを選択してください',
        'labels' => [
            'plain' => 'スコープなし',
            'front' => 'フロント用',
            'admin' => '管理画面用',
        ],
        'map' => [
            'スコープなし' => 'plain',
            'フロント用' => 'front',
            '管理画面用' => 'admin',
        ]
    ],
    'file_type' => [
        'prompt' => 'カスタム用ファイルの種類を選択してください',
        'labels' => [
            'core' => 'コア用ファイル（core）',
            'plugin' => 'プラグイン用ファイル（plugin）',
        ],
    ],
    'license' => [
        'prompt' => 'ライセンスを選択してください（未入力の場合はライセンス表記なしが選択されます）',
        'using_custom_license' => 'カスタムディレクトリ用ライセンスを使用します: :license',
        'failed_to_read_license' => 'カスタムライセンスファイルの読み込みに失敗しました: :error',
        'warnings' => [
            'plugin_missing' => '⚠️ プラグイン [:plugin] のライセンス情報が見つかりません。ライセンス表記はスキップされます。',
            'template_not_specified' => '⚠️ ライセンス情報にテンプレートパスが指定されていません。',
            'template_not_found' => '⚠️ テンプレートファイルが存在しません: :path',
            'template_missing_core' => 'テンプレートが見つかりません: :path',
            'notice' => <<<EOT
[!] 注意: 作成されるファイルは、任意のライセンスを選べますが、以下の点にご注意ください。
・コアのクラスを継承（extends）、トレイトを使用（use）、インターフェースを実装（implements）した場合、または、コアのコードを直接利用した場合には、コア(AGPL)のライセンスが伝搬します。
・ただし、完全に独立したカスタムディレクトリに配置し、コアとの連携をイベントリスナーなどで疎結合に保てば、独自のライセンスを選択できます。
・AGPLを選択した場合で、フォームなどの一般ユーザーからのデータ入力可能な機能を追加した場合には、ソースコード公開が必要となります。この場合、GPLやMIT等の別ライセンスでの作成を推奨します。
・特定クライアント向けの納品で、社内利用・一般への非公開が前提の場合は、ライセンス表記は不要です。
・再配布やSaaS提供の可能性がある場合は、ライセンス条件を慎重にご確認ください。
EOT,
        ],
    ],
    'plugin' => [
        'prompt' => 'プラグインを選択してください',
        'not_found' => 'プラグインが見つかりません。plugins ディレクトリに少なくとも1つのプラグインが必要です。',
    ],
    'files' => [
        'category' => [
            'controllers' => 'コントローラ',
            'requests'    => 'リクエスト',
            'services'    => 'サービス',
            'repositories' => 'リポジトリ',
            'models' => 'モデル',
            'default'    => 'ファイル',
        ],
        'created' => 'が作成されました！',
    ],
    'make' => [
        'options' => [
            'all' => 'マイグレーション、シーダー、ファクトリ、ポリシー、リソースコントローラー、フォームリクエストを全て生成します',
            'controller' => 'モデルの新しいコントローラーを作成します',
            'factory' => 'モデルの新しいファクトリを作成します',
            'migration' => 'モデルの新しいマイグレーションファイルを作成します',
            'policy' => 'モデルの新しいポリシーを作成します',
            'seed' => 'モデルの新しいシーダーを作成します',
            'api' => 'コントローラーからcreateとeditメソッドを除外します',
            'requests' => 'コントローラーのフォームリクエストクラスを作成します',
            'invokable' => '単一メソッドの呼び出し可能なコントローラークラスを生成します',
            'model' => '指定されたモデルのリソースコントローラーを生成します',
            'parent' => 'ネストされたリソースコントローラークラスを生成します',
            'resource' => 'リソースコントローラークラスを生成します',
            'singleton' => 'シングルトンリソースコントローラークラスを生成します',
            'creatable' => 'createとstoreメソッドを持つリソースコントローラーを生成します',
        ],
        'common' => [
            'class_name' => 'クラス名',
            'force' => '既存のファイルを上書きします',
        ]
    ],
    'file' => [
        'already_exists' => 'ファイルが既に存在します: :path',
        'created' => 'ファイルを作成しました: :path',
    ]
];
