<?php

return [
    'file' => [
        'already_exists' => 'ファイル [ :path ] は既に存在します。',
        'created' => 'ファイル [ :path ] を作成しました。',
    ],
    'make_plugin' => [
        'enter_plugin_name' => 'プラグイン名を入力してください',
        'enter_author_name' => '開発者名を入力してください',
        'enter_email' => '開発者のメールアドレスを入力してください',
        'enter_website_url' => '開発者のWebサイトURLを入力してください（https://の後の部分のみ入力）',
        'select_license' => 'ライセンスを選択してください',
        'enter_license_number' => 'ライセンス番号を入力してください（デフォルト：なし）：',
        'confirm_install' => 'プラグインをインストールしますか？',
        'confirm_enable' => 'プラグインを有効化しますか？',
        'success' => 'プラグイン \':pluginName\' が正常に作成されました！',
        'already_exists' => 'プラグイン \':pluginName\' は既に存在します。',
        'installed' => 'プラグイン \':pluginName\' がインストールされました。',
        'enabled' => 'プラグイン \':pluginName\' が有効化されました。',
        'disabled' => 'プラグイン \':pluginName\' が無効化されました。',
        'not_found' => 'プラグイン \':pluginName\' がデータベースに見つかりません。',
        'no_assets' => 'プラグイン \':pluginName\' のアセットディレクトリが見つかりません。',
        'files' => [
            'service_provider' => 'サービスプロバイダ [:className] がプラグイン [:pluginName] 用に作成されました。',
            'controller' => 'コントローラ [:className] がプラグイン [:pluginName] 用に作成されました。',
            'model' => 'モデル [:className] がプラグイン [:pluginName] 用に作成されました。',
            'policy' => 'ポリシー [:className] がプラグイン [:pluginName] 用に作成されました。',
            'listener' => 'リスナー [:className] がプラグイン [:pluginName] 用に作成されました。',
            'test' => 'テスト [:className] がプラグイン [:pluginName] 用に作成されました。',
            'migration' => 'マイグレーション :className がプラグイン [:pluginName] 用に作成されました。',
            'resource' => 'リソース [:className] がプラグイン [:pluginName] 用に作成されました。',
            'command' => 'コマンド [:className] がプラグイン [:pluginName] 用に作成されました。',
            'job' => 'ジョブ [:className] がプラグイン [:pluginName] 用に作成されました。',
            'notification' => '通知 [:className] がプラグイン [:pluginName] 用に作成されました。',
            'seeder' => 'シーダー :className がプラグイン [:pluginName] 用に作成されました。',
            'factory' => 'ファクトリ [:className] がプラグイン [:pluginName] 用に作成されました。',
            'routes' => 'ルートファイルがプラグイン [:pluginName] 用に作成されました。',
            'config' => 'コンフィグファイルがプラグイン [:pluginName] 用に作成されました。',
            'lang' => '言語ファイル（en & ja）がプラグイン [:pluginName] 用に作成されました。',
            'vite' => 'Vite設定ファイルがプラグイン [:pluginName] 用に作成されました。',
            'composer' => 'composer.jsonファイルがプラグイン [:pluginName] 用に作成されました。',
            'readme' => 'README.mdがプラグイン [:pluginName] 用に作成されました。',
            'license_info' => 'ライセンス情報ファイルがプラグイン [:pluginName] 用に作成されました。',
            'database_seeder' => 'データベースシーダー [:className] がプラグイン [:pluginName] 用に作成されました。',
            'phpunit_config' => 'PHPUnit設定ファイルがプラグイン [:pluginName] 用に作成されました。',
        ],
        'license_options' => [
            'gpl' => 'GPL-3.0',
            'agpl' => 'AGPL-3.0',
            'mit' => 'MIT',
            'apache' => 'Apache-2.0',
            'bsd3' => 'BSD-3-Clause',
            'lgpl' => 'LGPL-3.0',
            'commercial' => '商用',
            'custom' => 'カスタムライセンス'
        ],
        'config' => [
            'description' => 'プラグイン用の新しい設定ファイルを作成します',
        ],
        'installation' => [
            'installed' => 'プラグイン \':pluginName\' をインストールしました。',
            'migrating' => 'マイグレーションを実行中...',
            'enable_confirm' => 'プラグイン \':pluginName\' を有効化しますか？',
            'enable_skipped' => 'プラグイン \':pluginName\' は無効のままです。有効化するには管理画面または `php artisan plugin:enable :pluginName` コマンドを使用してください。',
            'composer_parse_error' => 'composer.json の解析に失敗しました: :error'
        ]
    ],  
    'plugin' => [
        'prompt' => 'プラグインを選択してください',
        'not_found' => '指定されたプラグインが見つかりません。',
        'not_exists' => '指定されたプラグインは存在しません。',
    ],
    'plugin_install' => [
        'description' => 'プラグインをインストールし、データベースに登録し、マイグレーションを実行し、オートロードを更新します。',
    ],
    'plugin_disable' => [
        'description' => 'プラグインを無効にし、シンボリックリンクを削除します',
    ],
    'plugin_enable' => [
        'description' => 'プラグインを有効にし、必要なシンボリックリンクを作成します',
    ],
    'plugin_symlink' => [
        'description' => 'プラグインアセットのシンボリックリンクを管理します',
        'invalid_action' => '無効なアクションです。"create" または "remove" を使用してください。',
        'created' => 'プラグインのシンボリックリンクを作成しました: :plugin',
        'removed' => 'プラグインのシンボリックリンクを削除しました: :plugin',
    ],
    'plugin_autoload_sync' => [
        'description' => 'プラグインをcomposer.jsonのPSR-4設定と同期します（オプションでクリーンアップも可能）。',
        'success' => 'Composerのオートロードが更新されました（プラグインが同期されました）。',
    ],
    'plugin_uninstall' => [
        'description' => 'プラグインをアンインストールし、データベースとファイルを削除します。',
        'not_found' => 'プラグイン \':pluginName\' は見つかりません。',
        'rollback_running' => 'マイグレーションのロールバックを実行中...',
        'rollback_confirm' => 'プラグイン \':pluginName\' に関連するデータベースのテーブルを削除しますか？',
        'rollback_skipped' => 'データベースのロールバックはスキップされました。',
        'directory_deleted' => 'プラグインのディレクトリ \':path\' を削除しました。',
        'directory_not_exists' => 'プラグインのディレクトリは既に存在しません。',
        'directory_not_deleted' => 'プラグインのディレクトリは削除されませんでした。',
        'delete_confirm' => 'プラグイン \':pluginName\' のディレクトリとファイルを削除しますか？',
        'database_removed' => 'プラグイン \':pluginName\' をデータベースから削除しました。',
        'completed' => 'プラグイン \':pluginName\' のアンインストールが完了しました。',
    ],
    'class' => [
        'enter_class_name' => 'クラス名を入力してください',
        'class_name_required' => 'クラス名は必須です',
        'enter_provider_class_name' => 'プロバイダークラス名を入力してください（例：MyServiceProvider）',
        'provider_class_required' => 'プロバイダークラス名は必須です',
        'enter_route_name' => 'ルートファイル名を入力してください（例：web, admin, api）',
        'route_name_required' => 'ルートファイル名は必須です',
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
        'select_route_type' => 'ルートタイプを選択してください（1-3）:',
        'enter_route_type' => 'ルートタイプを入力してください（1=Web, 2=管理画面, 3=API）[1]:',
        'route_types' => [
            'web' => 'Web',
            'web_description' => '通常のWebページ用',
            'admin' => '管理画面',
            'admin_description' => '管理画面用ルート',
            'api' => 'API',
            'api_description' => 'APIエンドポイント用',
        ],
        'options' => [
            'all' => 'マイグレーション、シーダー、ファクトリ、ポリシー、リソースコントローラ、フォームリクエストを生成',
            'controller' => 'モデル用の新しいコントローラを作成',
            'factory' => 'モデル用の新しいファクトリを作成',
            'migration' => 'モデル用の新しいマイグレーションファイルを作成',
            'policy' => 'モデル用の新しいポリシーを作成',
            'seed' => 'モデル用の新しいシーダーを作成',
            'api' => 'コントローラからcreateとeditメソッドを除外',
            'requests' => 'コントローラ用のフォームリクエストクラスを作成',
            'invokable' => '単一メソッドの呼び出し可能なコントローラクラスを生成',
            'model' => '指定されたモデル用のリソースコントローラを生成',
            'parent' => 'ネストされたリソースコントローラクラスを生成',
            'resource' => 'リソースコントローラクラスを生成',
            'singleton' => 'シングルトンリソースコントローラクラスを生成',
            'creatable' => 'createとstoreメソッドを持つリソースコントローラを生成',
        ],
        'common' => [
            'class_name' => 'クラス名',
            'force' => '既存のファイルを上書き',
        ]
    ],
    'plugin_autoload' => [
        'description' => 'composer.jsonのautoloadに新しいプラグインディレクトリを追加します（クリーンアップは行いません）。',
        'added' => 'composer.jsonのautoloadに新しいプラグインディレクトリを追加しました。',
        'no_changes' => '新しいプラグインディレクトリは見つかりませんでした。変更はありません。'
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
            'custom_core' => 'コア用ファイル（core）',
            'custom_plugin' => 'プラグイン用ファイル（plugin）',
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

];
