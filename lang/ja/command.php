<?php

return [
    // ========================================
    // DixlaseDeveloper関連のキーは
    // plugins/DixlaseDeveloper/lang/*/command.php に移動しました
    // ========================================

    'plugin' => [
        'prompt' => 'プラグインを選択してください',
        'not_found' => '指定されたプラグインが見つかりません。',
        'not_exists' => '指定されたプラグインは存在しません。',
        'not_selected' => 'プラグインが選択されませんでした。',
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
    'theme_uninstall' => [
        'description' => 'テーマをアンインストールします（ファイルは保持されます）',
        'theme_name_prompt' => 'アンインストールするテーマ名',
        'theme_not_found' => 'テーマ \':themeName\' はデータベースに見つかりませんでした。',
        'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
        'cannot_uninstall_enabled' => '有効なテーマ \':themeName\' をアンインストールできません。',
        'disable_first' => 'アンインストールする前に、まず `dls:theme:disable` コマンドでテーマを無効化してください。',
        'confirmation' => '本当にテーマ \':themeName\' をアンインストールしますか?',
        'cancelled' => 'アンインストールはキャンセルされました。',
        'uninstalled' => 'テーマをアンインストールしました: :themeName',
        'files_preserved' => 'テーマのファイルとディレクトリは保持されました。',
        'delete_hint' => 'ファイルを削除するには `php artisan theme:delete <directory>` コマンドを実行してください。',
    ],
    'theme_install' => [
        'description' => 'テーマをデータベースにインストールします',
        'theme_name_prompt' => 'インストールするテーマ名',
        'theme_not_found' => 'テーマ \':themeName\' はテーマディレクトリに存在しません。',
        'already_registered' => 'テーマ \':themeName\' は既にデータベースに登録されています。',
        'registered' => 'テーマ \':themeName\' をデータベースに登録しました。',
        'enable_help' => '次のコマンドで有効化できます: php artisan dls:theme:enable :themeName',
    ],
    'theme_disable' => [
        'description' => 'テーマを無効化します',
        'theme_name_prompt' => '無効化するテーマ名',
        'no_enabled_themes' => '有効なテーマが見つかりませんでした。',
        'theme_not_found' => 'テーマ \':themeName\' が見つかりません。',
        'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
        'already_disabled' => 'テーマ \':themeName\' は既に無効化されています。',
        'disabled' => 'テーマを無効化しました: :themeName',
        'list_headers' => ['名前', 'スラッグ'],
        'disable_help' => 'テーマを無効化するには、次のコマンドを実行してください: php artisan dls:theme:disable <theme-name>',
    ],
    'theme_enable' => [
        'description' => 'テーマを切り替えます（有効化するテーマを選択）',
        'theme_name_prompt' => '切り替えるテーマ名',
        'no_themes' => 'データベースにテーマが見つかりませんでした。',
        'no_installed_themes' => 'インストール済みのテーマがありません。',
        'theme_not_found' => 'テーマ \':themeName\' が見つかりません。',
        'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
        'install_first' => 'テーマを有効化する前に、まず `dls:theme:install` コマンドでテーマをインストールしてください。',
        'disabled' => 'テーマを無効化しました: :themeName',
        'already_enabled' => 'テーマ \':themeName\' は既に有効化されています。',
        'enabled' => 'テーマを有効化しました: :themeName',
        'list_headers' => ['名前', 'スラッグ', 'インストール', 'ステータス'],
        'installed' => 'インストール済み',
        'not_installed_status' => '未インストール',
        'status_enabled' => '有効',
        'status_disabled' => '無効',
        'select_prompt' => '有効化するテーマを選択してください',
        'current_marker' => '(現在有効)',
        'selection_error' => 'テーマの選択に失敗しました。',
        'symlink_warning' => 'シンボリックリンクの更新に失敗しましたが、テーマの切り替えは完了しました。',
    ],
    'theme_switch' => [
        'description' => 'テーマを切り替えます（有効化するテーマを選択）',
        'theme_name_prompt' => '切り替えるテーマ名',
        'no_installed_themes' => 'インストール済みのテーマがありません。',
        'theme_not_found' => 'テーマ \':themeName\' が見つかりません。',
        'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
        'install_first' => 'テーマを切り替える前に、まず `dls:theme:install` コマンドでテーマをインストールしてください。',
        'disabled' => '前のテーマを無効化しました: :themeName',
        'already_enabled' => 'テーマ \':themeName\' は既に有効化されています。',
        'switched' => 'テーマを切り替えました: :themeName',
        'select_prompt' => '切り替えるテーマを選択してください',
        'current_marker' => '(現在有効)',
        'selection_error' => 'テーマの選択に失敗しました。',
        'symlink_warning' => 'シンボリックリンクの更新に失敗しましたが、テーマの切り替えは完了しました。',
    ],
    'theme_delete' => [
        'description' => 'テーマのファイルとディレクトリを削除します（アンインストール済みである必要があります）',
        'theme_directory_prompt' => '削除するテーマのディレクトリ名',
        'force_option' => '確認なしで強制的に削除します',
        'not_found' => 'テーマディレクトリ \':directory\' は見つかりません。',
        'still_installed' => 'テーマ \':themeName\' はまだインストールされています。',
        'still_enabled' => 'テーマ \':themeName\' はまだ有効化されています。',
        'uninstall_first' => '削除する前に、まず `dls:theme:uninstall` コマンドでテーマをアンインストールしてください。',
        'disable_first' => '削除する前に、まず別のテーマに切り替えてください。',
        'confirm' => 'テーマディレクトリ \':directory\' とその中のすべてのファイルを削除しますか?この操作は取り消せません。',
        'cancelled' => '削除がキャンセルされました。',
        'deleted' => 'テーマディレクトリ \':path\' を削除しました。',
        'failed' => 'テーマディレクトリの削除に失敗しました: :error',
        'database_removed' => 'テーマ \':themeName\' をデータベースから削除しました。',
        'completed' => 'テーマ \':directory\' の削除が完了しました。',
    ],
    // make_theme は DixlaseDeveloper に移動
    'plugin_symlink' => [
        'description' => 'プラグインアセットのシンボリックリンクを管理します',
        'invalid_action' => '無効なアクションです。"create" または "remove" を使用してください。',
        'created' => 'プラグインのシンボリックリンクを作成しました: :plugin',
        'removed' => 'プラグインのシンボリックリンクを削除しました: :plugin',
    ],
    'theme_symlink' => [
        'description' => 'テーマアセットのシンボリックリンクを管理します',
        'invalid_action' => '無効なアクションです。"create" または "remove" を使用してください。',
        'created' => 'テーマのシンボリックリンクを作成しました: :theme',
        'removed' => 'テーマのシンボリックリンクを削除しました: :theme',
    ],
    'plugin_autoload_sync' => [
        'description' => 'プラグインをcomposer.jsonのPSR-4設定と同期します（オプションでクリーンアップも可能）。',
        'success' => 'Composerのオートロードが更新されました（プラグインが同期されました）。',
    ],
    'plugin_uninstall' => [
        'description' => 'プラグインをアンインストールし、データベースから削除します（ファイルは保持されます）。',
        'not_found' => 'プラグイン \':pluginName\' は見つかりません。',
        'still_enabled' => 'プラグイン \':pluginName\' は有効化されています。',
        'disable_first' => 'アンインストールする前に、まず `plugin:disable` コマンドでプラグインを無効化してください。',
        'force_disabling' => '--forceオプションが指定されたため、プラグイン \':pluginName\' を強制的に無効化します。',
        'confirm' => 'プラグイン \':pluginName\' をアンインストールしますか？この操作はデータベースからプラグイン情報を削除します。',
        'cancelled' => 'アンインストールがキャンセルされました。',
        'rollback_running' => 'マイグレーションのロールバックを実行中...',
        'rollback_confirm' => 'プラグイン \':pluginName\' に関連するデータベースのテーブルを削除しますか？',
        'rollback_skipped' => 'データベースのロールバックはスキップされました。',
        'files_preserved' => 'プラグインのファイルとディレクトリは保持されました。',
        'database_removed' => 'プラグイン \':pluginName\' をデータベースから削除しました。',
        'completed' => 'プラグイン \':pluginName\' のアンインストールが完了しました。',
        'delete_hint' => 'ファイルを削除するには `php artisan plugin:delete <directory>` コマンドを実行してください。',
    ],
    'plugin_delete' => [
        'description' => 'プラグインのファイルとディレクトリを削除します（アンインストール済みである必要があります）。',
        'not_found' => 'プラグインディレクトリ \':directory\' は見つかりません。',
        'still_installed' => 'プラグイン \':pluginName\' はまだインストールされています。',
        'uninstall_first' => '削除する前に、まず `plugin:uninstall` コマンドでプラグインをアンインストールしてください。',
        'confirm' => 'プラグインディレクトリ \':directory\' とその中のすべてのファイルを削除しますか？この操作は取り消せません。',
        'cancelled' => '削除がキャンセルされました。',
        'deleted' => 'プラグインディレクトリ \':path\' を削除しました。',
        'failed' => 'プラグインディレクトリの削除に失敗しました: :error',
        'completed' => 'プラグイン \':directory\' の削除が完了しました。',
    ],
    // class, files, make は DixlaseDeveloper に移動
    'plugin_autoload' => [
        'description' => 'composer.jsonのautoloadに新しいプラグインディレクトリを追加します（クリーンアップは行いません）。',
        'added' => 'composer.jsonのautoloadに新しいプラグインディレクトリを追加しました。',
        'no_changes' => '新しいプラグインディレクトリは見つかりませんでした。変更はありません。'
    ],
    // scope は DixlaseDeveloper に移動
    'theme' => [
        'not_found' => 'テーマ \':name\' が見つかりません。',
        'no_themes_found' => 'テーマが見つかりません。',
        'select_theme' => 'テーマを選択してください',
    ],
    // class_name_prompt, class_name_required, file_type, license は DixlaseDeveloper に移動
    'production_warning' => '本番環境で :action を実行しようとしています。',
    'production_confirm' => '続行してもよろしいですか？',

    // Git同期コマンド
    'git_sync' => [
        'git_not_found' => 'Gitリポジトリが見つかりません。.gitディレクトリが存在しません。',
        'gitignore_not_found' => '.gitignoreファイルが見つかりません。',
        'scanning' => 'ディレクトリをスキャン中...',
        'plugins_found' => 'プラグインディレクトリ:',
        'themes_found' => 'テーマディレクトリ:',
        'to_add' => '追加:',
        'to_remove' => '削除:',
        'exclude_in_sync' => '✓ .git/info/exclude は同期済みです。変更は必要ありません。',
        'gitignore_in_sync' => '✓ .gitignore は同期済みです。変更は必要ありません。',
        'dry_run' => 'ドライランモード。変更は行われませんでした。',
        'confirm_apply' => 'これらの変更を適用しますか？',
        'cancelled' => '操作がキャンセルされました。',
        'exclude_synced' => '✓ .git/info/exclude を正常に同期しました',
        'gitignore_synced' => '✓ .gitignore を正常に同期しました',
        'already_exists' => '除外ルールは既に存在します: :path',
        'added' => '除外ルールを追加しました: :path',
        'removed' => '除外ルールを削除しました: :path',
        'failed' => '操作に失敗しました: :error',
    ],

    // ログイン試行履歴クリーンアップコマンド
    'cleanup_login_attempts' => [
        'days_zero_warning' => '日数が0に設定されています - すべてのログイン試行記録が削除されます。',
        'confirm_delete_all' => 'すべてのログイン試行記録を削除してもよろしいですか？この操作は元に戻すことができません。',
        'operation_cancelled' => '操作がキャンセルされました。',
        'deleting_all' => 'すべてのログイン試行記録を削除しています...',
        'deleted_all_success' => 'すべての :count 件のログイン試行記録を正常に削除しました。',
        'no_records_found' => '削除するログイン試行記録が見つかりませんでした。',
        'invalid_days' => '日数は正の整数である必要があります。すべてのレコードを削除する場合は --all を使用してください。',
        'cleaning_up' => ':days 日より古いログイン試行記録をクリーンアップしています...',
        'deleted_old_success' => ':count 件の古いログイン試行記録を正常に削除しました。',
        'no_old_records_found' => '削除する古いログイン試行記録が見つかりませんでした。',
    ],

    // パスワードリセットトークンクリーンアップコマンド
    'cleanup_password_reset_tokens' => [
        'days_zero_warning' => '日数が0に設定されています - すべてのパスワードリセットトークン記録が削除されます。',
        'confirm_delete_all' => 'すべてのパスワードリセットトークン記録を削除してもよろしいですか？この操作は元に戻すことができません。',
        'operation_cancelled' => '操作がキャンセルされました。',
        'deleting_all' => 'すべてのパスワードリセットトークン記録を削除しています...',
        'deleted_all_success' => 'すべての :count 件のパスワードリセットトークン記録を正常に削除しました。',
        'no_records_found' => '削除するパスワードリセットトークン記録が見つかりませんでした。',
        'invalid_days' => '日数は正の整数である必要があります。すべてのレコードを削除する場合は --all を使用してください。',
        'cleaning_up' => ':days 日より古いパスワードリセットトークンをクリーンアップしています...',
        'deleted_old_success' => ':count 件の古いパスワードリセットトークン記録を正常に削除しました。',
        'no_old_records_found' => '削除する古いパスワードリセットトークン記録が見つかりませんでした。',
    ],

    // 信頼されたデバイスクリーンアップコマンド
    'cleanup_trusted_devices' => [
        'days_zero_warning' => '日数が0に設定されています - すべての信頼されたデバイス記録が削除されます。',
        'confirm_delete_all' => 'すべての信頼されたデバイス記録を削除してもよろしいですか？この操作は元に戻すことができません。',
        'operation_cancelled' => '操作がキャンセルされました。',
        'deleting_all' => 'すべての信頼されたデバイス記録を削除しています...',
        'deleted_all_success' => 'すべての :count 件の信頼されたデバイス記録を正常に削除しました。',
        'no_records_found' => '削除する信頼されたデバイス記録が見つかりませんでした。',
        'invalid_days' => '日数は正の整数である必要があります。すべてのレコードを削除する場合は --all を使用してください。',
        'cleaning_up' => ':days 日より古い信頼されたデバイスをクリーンアップしています...',
        'deleted_old_success' => ':count 件の古い信頼されたデバイス記録を正常に削除しました。',
        'no_old_records_found' => '削除する古い信頼されたデバイス記録が見つかりませんでした。',
    ],

    // 二段階認証トークンクリーンアップコマンド
    'cleanup_two_factor_tokens' => [
        'days_zero_warning' => '日数が0に設定されています - すべての二段階認証トークン記録が削除されます。',
        'confirm_delete_all' => 'すべての二段階認証トークン記録を削除してもよろしいですか？この操作は元に戻すことができません。',
        'operation_cancelled' => '操作がキャンセルされました。',
        'deleting_all' => 'すべての二段階認証トークン記録を削除しています...',
        'deleted_all_success' => 'すべての :count 件の二段階認証トークン記録を正常に削除しました。',
        'no_records_found' => '削除する二段階認証トークン記録が見つかりませんでした。',
        'invalid_days' => '日数は正の整数である必要があります。すべてのレコードを削除する場合は --all を使用してください。',
        'cleaning_up' => ':days 日より古い二段階認証トークンをクリーンアップしています...',
        'deleted_old_success' => ':count 件の古い二段階認証トークン記録を正常に削除しました。',
        'no_old_records_found' => '削除する古い二段階認証トークン記録が見つかりませんでした。',
    ],

    // キャッシュクリーンアップコマンド
    'cleanup_cache' => [
        'confirm_delete_all' => 'すべてのキャッシュエントリとロックを削除してもよろしいですか？この操作は元に戻すことができません。',
        'operation_cancelled' => '操作がキャンセルされました。',
        'deleting_all' => 'すべてのキャッシュエントリとロックを削除しています...',
        'deleted_all_success' => ':cache_count 件のキャッシュエントリと :locks_count 件のキャッシュロックを正常に削除しました。',
        'no_records_found' => '削除するキャッシュ記録が見つかりませんでした。',
        'cleaning_all' => 'すべてのキャッシュエントリとロックをクリーンアップしています...',
        'cleaning_expired' => '期限切れのキャッシュエントリとロックをクリーンアップしています...',
        'deleted_expired_success' => ':cache_count 件の期限切れキャッシュエントリと :locks_count 件の期限切れキャッシュロックを正常に削除しました。',
        'no_expired_records_found' => '削除する期限切れキャッシュ記録が見つかりませんでした。',
    ],

    // セッションクリーンアップコマンド
    'cleanup_sessions' => [
        'confirm_delete_all' => 'すべてのセッション記録を削除してもよろしいですか？この操作は元に戻すことができません。',
        'operation_cancelled' => '操作がキャンセルされました。',
        'deleting_all' => 'すべてのセッション記録を削除しています...',
        'deleted_all_success' => 'すべての :count 件のセッション記録を正常に削除しました。',
        'no_records_found' => '削除するセッション記録が見つかりませんでした。',
        'invalid_days' => '日数は正の整数である必要があります。すべてのレコードを削除する場合は --all を使用してください。',
        'cleaning_up' => ':days 日より古いセッションをクリーンアップしています...',
        'deleted_old_success' => ':count 件の古いセッション記録を正常に削除しました。',
        'no_old_records_found' => '削除する古いセッション記録が見つかりませんでした。',
    ],

    // カスタムファイル作成コマンド
    'make_custom' => [
        'observer' => [
            'description' => 'カスタムディレクトリにObserverクラスを作成します',
        ],
        'service' => [
            'description' => 'カスタムディレクトリにServiceクラスを作成します',
        ],
        'validator' => [
            'description' => 'カスタムディレクトリにValidatorクラスを作成します',
        ],
        'command' => [
            'description' => 'カスタムディレクトリにArtisanコマンドクラスを作成します',
        ],
    ],

    // デプロイコマンド
    'deploy' => [
        'init' => [
            'description' => 'dixlase-deploy.json設定ファイルを生成します',
            'already_exists' => '設定ファイルは既に存在します: :path',
            'use_force' => '上書きするには --force オプションを使用してください',
            'generated' => '設定ファイルを生成しました: :path',
            'next_steps' => '次のステップ:',
            'step_edit' => ':path を環境設定に合わせて編集してください',
            'step_env' => '機密情報用の環境変数を設定してください',
            'step_doctor' => "'php artisan deploy:doctor' を実行して設定を検証してください",
            'step_list' => "'php artisan deploy:list' を実行して利用可能な環境を確認してください",
            'gitignore_warning' => "重要: 認証情報を保護するため 'dixlase-deploy.json' を .gitignore に追加してください",
            'failed' => '設定ファイルの生成に失敗しました: :error',
        ],
        'doctor' => [
            'description' => 'デプロイ設定とシステム要件をチェックします',
            'title' => 'Dixlase Deploy Doctor',
            'checking_config' => '設定をチェック中...',
            'config_not_found' => '設定ファイルが見つかりません',
            'config_exists' => '設定ファイルが存在します',
            'config_valid' => '設定は有効です',
            'checking_requirements' => 'システム要件をチェック中...',
            'rsync_installed' => 'rsync がインストールされています',
            'rsync_not_installed' => 'rsync がインストールされていません（SSH同期に必要）',
            'lftp_installed' => 'lftp がインストールされています',
            'lftp_not_installed' => 'lftp がインストールされていません（FTP同期に必要）',
            'ssh_installed' => 'ssh がインストールされています',
            'ssh_not_installed' => 'ssh がインストールされていません',
            'mysql_installed' => 'mysql クライアントがインストールされています',
            'mysql_not_installed' => 'mysql クライアントがインストールされていません（データベース同期に必要）',
            'mysqldump_installed' => 'mysqldump がインストールされています',
            'mysqldump_not_installed' => 'mysqldump がインストールされていません（データベース同期に必要）',
            'gzip_installed' => 'gzip がインストールされています',
            'gzip_not_installed' => 'gzip がインストールされていません',
            'available_environments' => '利用可能な環境:',
            'failed_to_load' => '環境の読み込みに失敗しました: :error',
            'checks_failed' => 'いくつかのチェックが失敗しました。上記の問題を修正してください。',
            'all_passed' => 'すべてのチェックに合格しました！デプロイの準備ができています。',
        ],
        'list' => [
            'description' => '利用可能なデプロイ環境を一覧表示します',
            'config_not_found' => '設定ファイルが見つかりません。',
            'no_environments' => '環境が設定されていません。',
            'title' => '利用可能な環境',
            'local' => 'ローカル:',
            'url' => 'URL:',
            'path' => 'パス:',
            'db' => 'DB:',
            'connection' => '接続:',
            'host' => 'ホスト:',
            'failed' => '設定の読み込みに失敗しました: :error',
        ],
        'push' => [
            'description' => 'ローカルのDixlaseデータをリモート環境にプッシュします',
            'config_not_found' => '設定ファイルが見つかりません。',
            'environment_not_found' => "環境 ':environment' が設定に見つかりません。",
            'available_environments' => '利用可能な環境: :environments',
            'no_targets' => '同期対象が指定されていません。',
            'use_options' => '--plugins, --themes, --custom, --uploads, --database, または --all を使用してください',
            'push_to' => ':environment にプッシュ',
            'targets' => '対象: :targets',
            'tables' => 'テーブル: :tables',
            'dry_run' => '[ドライランモード]',
            'warning_overwrite' => '警告: :vhost のデータベースが上書きされます',
            'confirm' => ':environment にプッシュしてもよろしいですか？',
            'cancelled' => '操作がキャンセルされました。',
        ],
        'pull' => [
            'description' => 'リモート環境からローカルにDixlaseデータをプルします',
            'config_not_found' => '設定ファイルが見つかりません。',
            'environment_not_found' => "環境 ':environment' が設定に見つかりません。",
            'available_environments' => '利用可能な環境: :environments',
            'no_targets' => '同期対象が指定されていません。',
            'use_options' => '--plugins, --themes, --custom, --uploads, --database, または --all を使用してください',
            'pull_from' => ':environment からプル',
            'targets' => '対象: :targets',
            'tables' => 'テーブル: :tables',
            'dry_run' => '[ドライランモード]',
            'warning_overwrite' => '警告: ローカルデータベースが上書きされます',
            'confirm' => ':environment からプルしてもよろしいですか？',
            'cancelled' => '操作がキャンセルされました。',
        ],
        'common' => [
            'starting_push' => ':environment へのプッシュを開始...',
            'starting_pull' => ':environment からのプルを開始...',
            'connecting' => ':connection で接続中',
            'connected' => '接続成功',
            'connection_failed' => ':environment への接続に失敗しました',
            'executing_hooks' => ':timing フックを実行中...',
            'running_command' => '実行中: :command (:where)',
            'hook_failed' => 'フックが失敗しました: :output',
            'hook_output' => '出力: :output',
            'syncing' => ':target を同期中: :path',
            'sync_failed' => ':target の同期に失敗しました',
            'would_sync' => '[ドライラン] :local <-> :remote を同期します',
            'would_sync_db' => '[ドライラン] データベースを同期します',
            'push_completed' => ':environment へのプッシュが正常に完了しました',
            'push_completed_with_errors' => ':environment へのプッシュがエラーで完了しました',
            'pull_completed' => ':environment からのプルが正常に完了しました',
            'pull_completed_with_errors' => ':environment からのプルがエラーで完了しました',
        ],
    ],

    // バックアップコマンド
    'backup' => [
        // 共通メッセージ
        'starting' => 'バックアップを開始します...',
        'completed' => 'バックアップが完了しました！',
        'no_targets' => 'バックアップ対象が指定されていません。',
        'use_options' => '--all, --core, --plugins, --themes, --custom, --storage-public, --storage-private, --logs, --database を使用してください',
        'cannot_use_both_only' => '--files-only と --db-only を同時に使用することはできません。',
        'summary' => 'バックアップサマリー',
        'no_backups_created' => 'バックアップは作成されませんでした。',
        'files_saved' => 'ファイルバックアップ: :path',
        'database_saved' => 'データベースバックアップ: :path',

        // ファイルバックアップ
        'section_files' => 'ファイルバックアップ',
        'creating_file_backup' => 'ファイルバックアップを作成中: :file',
        'failed_to_create_zip' => 'ZIPファイルの作成に失敗しました。',
        'path_not_found' => 'パスが見つかりません: :path',
        'adding_target' => ':target を追加中: :path',
        'no_files_added' => 'バックアップに追加するファイルがありませんでした。',
        'file_backup_completed' => 'ファイルバックアップ完了: :file (:count ファイル, :size)',
        'no_paths_to_backup' => 'バックアップするパスがありません。',

        // データベースバックアップ
        'section_database' => 'データベースバックアップ',
        'creating_database_backup' => 'データベースバックアップを作成中: :file',
        'database_config_not_found' => 'データベース設定が見つかりません。',
        'running_mysqldump' => 'mysqldump を実行中...',
        'mysqldump_failed' => 'mysqldump が失敗しました: :error',
        'database_backup_completed' => 'データベースバックアップ完了: :file (テーブル: :tables, :size)',
        'all_tables' => 'すべてのテーブル',

        // バックアップ一覧
        'list' => [
            'title' => '利用可能なバックアップ',
            'file_backups' => 'ファイルバックアップ',
            'database_backups' => 'データベースバックアップ',
            'no_file_backups' => 'ファイルバックアップはありません。',
            'no_database_backups' => 'データベースバックアップはありません。',
            'filename' => 'ファイル名',
            'size' => 'サイズ',
            'date' => '作成日時',
            'backup_directory' => 'バックアップディレクトリ: :path',
        ],

        // バックアップクリーンアップ
        'cleanup' => [
            'invalid_days' => '日数は0以上の整数である必要があります。',
            'confirm_delete_all' => 'すべてのバックアップを削除してもよろしいですか？この操作は元に戻すことができません。',
            'confirm_delete_old' => ':days 日より古いバックアップを削除してもよろしいですか？',
            'cancelled' => '操作がキャンセルされました。',
            'starting' => '古いバックアップを削除中...',
            'no_backups_deleted' => '削除するバックアップはありませんでした。',
            'deleted_count' => ':count 件のバックアップを削除しました。',
        ],

        // 古いバックアップ削除
        'deleted_old_backup' => '古いバックアップを削除しました: :file',
    ],

    // ファイル整合性チェック
    'integrity' => [
        // ベースライン生成
        'generating_baseline' => 'ファイル整合性ベースラインを生成しています...',
        'baseline_exists' => '既存のベースラインが見つかりました（生成日: :date, バージョン: :version）',
        'overwrite_confirm' => '既存のベースラインを上書きしますか？',
        'cancelled' => '操作がキャンセルされました。',
        'scanning_files' => 'ファイルをスキャン中...',
        'saving_baseline' => 'ベースラインを保存中...',
        'baseline_success' => 'ベースラインが正常に生成されました。',
        'baseline_failed' => 'ベースラインの保存に失敗しました。',
        'baseline_generated' => 'ファイル整合性ベースラインを生成しました',
        'baseline_regenerated' => 'ファイル整合性ベースラインを再生成しました',

        // スキャン
        'starting_scan' => 'ファイル整合性スキャンを開始します...',
        'scope_not_supported' => 'スコープ ":scope" は現在サポートされていません。',
        'using_core_scope' => 'コアスコープを使用します。',
        'scanning' => 'スキャン中...',
        'scan_error' => 'スキャンエラー: :error',

        // 結果表示
        'status' => 'ステータス',
        'status_ok' => '正常',
        'status_warning' => '警告',
        'status_critical' => '重大',
        'files_scanned' => 'スキャンしたファイル数',
        'duration' => '実行時間',
        'summary' => 'サマリー',

        // 問題の詳細
        'changed_files' => '変更されたファイル (:count 件)',
        'added_files' => '追加されたファイル (:count 件)',
        'removed_files' => '削除されたファイル (:count 件)',
        'suspicious_files' => '疑わしいファイル (:count 件)',

        // 疑わしいファイルの理由
        'reason_php_in_uploads' => 'アップロードディレクトリ内のPHPファイル',
        'reason_unknown_php_in_public' => 'public直下の未知のPHPファイル',

        // 重大な警告
        'critical_warning' => '⚠️ 重大なセキュリティ問題が検出されました！',
        'critical_action_1' => '1. 疑わしいファイルを直ちに確認してください。',
        'critical_action_2' => '2. 不正なファイルが見つかった場合は削除してください。',
        'critical_action_3' => '3. システムのセキュリティ監査を実施してください。',

        // サマリーメッセージ
        'summary_changed' => ':count 件のファイルが変更されました',
        'summary_added' => ':count 件のファイルが追加されました',
        'summary_removed' => ':count 件のファイルが削除されました',
        'summary_suspicious' => ':count 件の疑わしいファイルがあります',
        'summary_ok' => '問題は検出されませんでした',

        // テーブル表示
        'item' => '項目',
        'value' => '値',
        'files_count' => 'ファイル数',
        'app_version' => 'アプリバージョン',
        'hash_algo' => 'ハッシュアルゴリズム',
        'generated_at' => '生成日時',
    ],
];
