<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Illuminate\Support\Facades\DB;

if (! function_exists('is_vite_dev_server')) {
    /**
     * Vite開発サーバーが有効かどうかを判定
     * ローカル環境かつhotファイルが存在する場合にtrue
     */
    function is_vite_dev_server(): bool
    {
        return app()->environment('local') && file_exists(public_path('hot'));
    }
}

if (! function_exists('get_csp_nonce_attr')) {
    /**
     * CSP nonce属性を取得
     */
    function get_csp_nonce_attr(): string
    {
        return function_exists('csp_nonce_attr') ? ' '.csp_nonce_attr() : '';
    }
}

if (! function_exists('render_vite_assets')) {
    /**
     * Vite開発サーバー用のアセットタグを生成
     *
     * @param  array  $files  Viteで読み込むファイルパスの配列
     * @param  bool  $addXCloak  x-cloak用スタイルを追加するか
     * @param  bool  $addFoucPrevention  FOUC防止スタイルを追加するか
     */
    function render_vite_assets(array $files, bool $addXCloak = true, bool $addFoucPrevention = false): string
    {
        $output = '';

        if (! empty($files)) {
            // CSP nonceを最初に取得（シングルトンなので同じ値が返される）
            $nonce = function_exists('csp_nonce') ? csp_nonce() : null;

            // Viteディレクティブを描画（配列構文で複数エントリーポイントに対応）
            $viteOutput = \Illuminate\Support\Facades\Blade::render(
                '@vite(['.implode(', ', array_map(fn ($file) => "'{$file}'", $files)).'])'
            );

            // Viteが生成したスクリプトタグのnonceを置き換え
            if ($nonce) {
                // 既存のnonce属性を削除してから新しいnonceを追加
                $viteOutput = preg_replace(
                    '/<script\s+nonce="[^"]*"\s*/i',
                    '<script ',
                    $viteOutput
                );
                // カスタムnonceを追加
                $viteOutput = preg_replace(
                    '/<script\s+(type="module"\s+)?src=/i',
                    '<script nonce="'.$nonce.'" $1src=',
                    $viteOutput
                );
            }

            $output .= $viteOutput;
        }

        if ($addXCloak) {
            $output .= '<style>[x-cloak]{display:none!important;}</style>';
        }

        if ($addFoucPrevention) {
            $output .= '<style>body{opacity:0;visibility:hidden;}</style>';
            $nonceAttr = get_csp_nonce_attr();
            $output .= <<<HTML
<script{$nonceAttr}>
    window.addEventListener('load', () => {
        document.body.style.visibility = 'visible';
        document.body.style.opacity = '1';
    });
</script>
HTML;
        }

        return $output;
    }
}

if (! function_exists('render_css_link')) {
    /**
     * CSSリンクタグを生成
     *
     * @param  string  $href  CSSファイルのURL
     */
    function render_css_link(string $href): string
    {
        return '<link rel="stylesheet" href="'.$href.'">';
    }
}

if (! function_exists('render_js_script')) {
    /**
     * JSスクリプトタグを生成（CSP nonce対応）
     *
     * @param  string  $src  JSファイルのURL
     */
    function render_js_script(string $src): string
    {
        $nonce = get_csp_nonce_attr();

        return '<script type="module" src="'.$src.'"'.$nonce.'></script>';
    }
}

if (! function_exists('render_x_cloak_style')) {
    /**
     * x-cloak用スタイルタグを生成（CSP nonce対応）
     */
    function render_x_cloak_style(): string
    {
        $nonce = get_csp_nonce_attr();

        return '<style'.$nonce.'>[x-cloak]{display:none!important;}</style>';
    }
}

if (! function_exists('get_active_theme_directory')) {
    /**
     * アクティブなテーマのディレクトリ名を取得
     */
    function get_active_theme_directory(): string
    {
        static $themeDirectory = null;

        if ($themeDirectory === null) {
            try {
                $themeSetting = DB::table('theme_settings')
                    ->where('key', 'enabled_theme_id')
                    ->first();

                if ($themeSetting && $themeSetting->value) {
                    $activeTheme = \App\Models\Theme::find((int) $themeSetting->value);
                    $themeDirectory = $activeTheme ? $activeTheme->directory : 'DixlaseOnePage';
                } else {
                    $themeDirectory = 'DixlaseOnePage';
                }
            } catch (\Exception $e) {
                $themeDirectory = 'DixlaseOnePage';
            }
        }

        return $themeDirectory;
    }
}

if (! function_exists('load_assets_from_manifest')) {
    /**
     * manifest.jsonからアセットを読み込む
     *
     * @param  string  $manifestPath  マニフェストファイルのパス
     * @param  string  $assetBasePath  アセットのベースパス
     * @param  array  $files  読み込むファイルの配列
     * @param  string  $sourceBasePath  ソースファイルのベースパス（マニフェストキー用）
     * @param  array  $alternativeKeys  代替のマニフェストキーパターン
     */
    function load_assets_from_manifest(
        string $manifestPath,
        string $assetBasePath,
        array $files,
        string $sourceBasePath = '',
        array $alternativeKeys = []
    ): string {
        $output = '';

        if (! file_exists($manifestPath) || empty($files)) {
            return $output;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (empty($manifest)) {
            return $output;
        }

        foreach ($files as $file) {
            $manifestKey = $sourceBasePath ? "{$sourceBasePath}/{$file}" : $file;
            $keysToTry = [$manifestKey, ...$alternativeKeys, $file];

            // プラグイン/テーマのマニフェストキーは resources/src/ からの相対パスの場合がある
            if ($sourceBasePath && preg_match('#(?:plugins|themes)/[^/]+/(resources/src.*)$#', $sourceBasePath, $m)) {
                $keysToTry[] = "{$m[1]}/{$file}";
            }

            foreach ($keysToTry as $key) {
                if (isset($manifest[$key])) {
                    $entry = $manifest[$key];

                    // CSSファイル（エントリ自体がCSSの場合）
                    if (isset($entry['file']) && str_ends_with($entry['file'], '.css')) {
                        $output .= render_css_link(asset($assetBasePath.$entry['file']));
                    }

                    // CSSファイル（JSエントリに紐づくCSS）
                    if (isset($entry['css'])) {
                        foreach ($entry['css'] as $css) {
                            $output .= render_css_link(asset($assetBasePath.$css));
                        }
                    }

                    // JSファイル
                    if (isset($entry['file']) && str_ends_with($entry['file'], '.js')) {
                        $output .= render_js_script(asset($assetBasePath.$entry['file']));
                    }

                    // フォントやその他のアセット
                    if (isset($entry['assets'])) {
                        foreach ($entry['assets'] as $assetFile) {
                            $ext = pathinfo($assetFile, PATHINFO_EXTENSION);
                            if (in_array($ext, ['woff', 'woff2', 'ttf', 'otf', 'eot'])) {
                                $output .= '<link rel="preload" as="font" href="'.asset($assetBasePath.$assetFile).'" type="font/'.$ext.'" crossorigin="anonymous">';
                            }
                        }
                    }

                    break;
                }
            }
        }

        return $output;
    }
}

if (! function_exists('load_static_assets')) {
    /**
     * 静的アセットファイルを直接読み込む（フォールバック用）
     *
     * @param  string  $assetBasePath  アセットのベースパス
     * @param  array  $files  読み込むファイルの配列
     */
    function load_static_assets(string $assetBasePath, array $files): string
    {
        $output = '';

        foreach ($files as $file) {
            $filePath = public_path("{$assetBasePath}/{$file}");
            if (file_exists($filePath)) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext === 'css') {
                    $output .= render_css_link(asset($assetBasePath.'/'.$file));
                } elseif ($ext === 'js') {
                    $output .= render_js_script(asset($assetBasePath.'/'.$file));
                }
            }
        }

        return $output;
    }
}

if (! function_exists('load_assets')) {
    /**
     * アセットを動的に読み込む関数（管理画面用）
     *
     * @param  string  $type  アセットのタイプ ('common', 'admin', 'theme', 'plugin')
     * @param  string|null  $name  テーマまたはプラグインの場合のディレクトリ名 (common, admin では null)
     * @param  array  $files  読み込むJSまたはCSSファイルのリスト
     */
    function load_assets(string $type, ?string $name, array $files): string
    {
        $output = '';
        $basePath = match ($type) {
            'common' => 'resources/src/common',
            'admin' => 'resources/src/admin',
            'theme' => "themes/{$name}/resources/src",
            'plugin' => "plugins/{$name}/resources/src",
            default => throw new InvalidArgumentException("Invalid type: {$type}"),
        };

        if (is_vite_dev_server()) {
            $viteFiles = array_map(fn ($file) => "{$basePath}/{$file}", $files);
            $output .= render_vite_assets($viteFiles, true, true);
        } else {
            $manifestPath = match ($type) {
                'common', 'admin' => public_path('assets/build/manifest.json'),
                'theme' => public_path("assets/themes/{$name}/manifest.json"),
                'plugin' => public_path("assets/plugins/{$name}/manifest.json"),
            };

            $assetBasePath = match ($type) {
                'common', 'admin' => 'assets/build/',
                'theme' => "assets/themes/{$name}/",
                'plugin' => "assets/plugins/{$name}/",
            };

            $output .= load_assets_from_manifest($manifestPath, $assetBasePath, $files, $basePath);

            // マニフェストが存在しない場合は静的アセットにフォールバック
            if ($output === '' && ! file_exists($manifestPath)) {
                $output .= load_static_assets(rtrim($assetBasePath, '/'), $files);
            }
        }

        return $output;
    }
}

if (! function_exists('get_plugin_declared_assets')) {
    /**
     * プラグインの plugin.json から宣言されたアセット情報を取得
     *
     * @return array{common: string[], admin: string[], front: string[]}|false|null
     *                                                                              アセット宣言がある場合は配列、false の場合はアセットなし、null の場合は未宣言
     */
    function get_plugin_declared_assets(string $pluginDirectory): array|false|null
    {
        static $cache = [];

        if (array_key_exists($pluginDirectory, $cache)) {
            return $cache[$pluginDirectory];
        }

        $pluginJsonPath = base_path("plugins/{$pluginDirectory}/plugin.json");
        if (! file_exists($pluginJsonPath)) {
            $cache[$pluginDirectory] = null;

            return null;
        }

        $data = json_decode(file_get_contents($pluginJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $cache[$pluginDirectory] = null;

            return null;
        }

        $assets = $data['declares']['assets'] ?? null;

        // assets === false の場合はアセットなし
        if ($assets === false) {
            $cache[$pluginDirectory] = false;

            return false;
        }

        // assets が配列の場合はそのまま返す
        if (is_array($assets)) {
            $cache[$pluginDirectory] = $assets;

            return $assets;
        }

        // 未宣言
        $cache[$pluginDirectory] = null;

        return null;
    }
}

if (! function_exists('get_theme_declared_assets')) {
    /**
     * テーマの theme.json から宣言されたアセット情報を取得
     *
     * @return array{common: string[], admin: string[], front: string[]}|false|null
     *                                                                              アセット宣言がある場合は配列、false の場合はアセットなし、null の場合は未宣言
     */
    function get_theme_declared_assets(string $themeDirectory): array|false|null
    {
        static $cache = [];

        if (array_key_exists($themeDirectory, $cache)) {
            return $cache[$themeDirectory];
        }

        $themeJsonPath = base_path("themes/{$themeDirectory}/theme.json");
        if (! file_exists($themeJsonPath)) {
            $cache[$themeDirectory] = null;

            return null;
        }

        $data = json_decode(file_get_contents($themeJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $cache[$themeDirectory] = null;

            return null;
        }

        $assets = $data['assets'] ?? null;

        if ($assets === false) {
            $cache[$themeDirectory] = false;

            return false;
        }

        if (is_array($assets)) {
            $cache[$themeDirectory] = $assets;

            return $assets;
        }

        $cache[$themeDirectory] = null;

        return null;
    }
}

if (! function_exists('load_active_assets')) {
    /**
     * 管理画面用：アクティブなテーマとプラグインのアセットを読み込む
     * プラグインセーフモード有効時はプラグインアセットをスキップ
     */
    function load_active_assets(): string
    {
        $output = '';

        // プラグイン・テーマのアセットを先に読み込み
        // （Alpine.data() 登録を common の Alpine.start() より前に実行するため）

        // プラグインセーフモード時はプラグインアセットをスキップ
        if (! session('safe_mode_plugins')) {
            // 有効なプラグインのアセットを読み込み
            try {
                $activePlugins = DB::table('plugins')->whereNotNull('enabled_at')->get();
                foreach ($activePlugins as $plugin) {
                    $declaredAssets = get_plugin_declared_assets($plugin->directory);

                    // assets === false または未宣言の場合はスキップ
                    if ($declaredAssets === false || $declaredAssets === null) {
                        continue;
                    }

                    $commonAssets = $declaredAssets['common'] ?? [];
                    $adminAssets = $declaredAssets['admin'] ?? [];
                    $allAssets = array_merge($commonAssets, $adminAssets);

                    if (! empty($allAssets)) {
                        $output .= load_assets('plugin', $plugin->directory, $allAssets);
                    }
                }
            } catch (\Exception $e) {
                // プラグインテーブルがない場合はスキップ
            }
        }

        // アクティブなテーマのアセットを読み込み
        $themeDirectory = get_active_theme_directory();
        $themeDeclaredAssets = get_theme_declared_assets($themeDirectory);

        if (is_array($themeDeclaredAssets)) {
            $themeCommonAssets = $themeDeclaredAssets['common'] ?? [];
            $themeAdminAssets = $themeDeclaredAssets['admin'] ?? [];
            $allThemeAssets = array_merge($themeCommonAssets, $themeAdminAssets);

            if (! empty($allThemeAssets)) {
                $output .= load_assets('theme', $themeDirectory, $allThemeAssets);
            }
        }

        // 管理画面用アセットを読み込み（Alpine.data() 登録を Alpine.start() より前に実行するため先に読み込む）
        $output .= load_assets('admin', null, ['js/app.js', 'scss/style.scss']);

        // 共通アセットを読み込み（Alpine.start() がここで実行される）
        $output .= load_assets('common', null, ['js/app.js', 'scss/style.scss']);

        return $output;
    }
}

if (! function_exists('load_theme_assets')) {
    /**
     * テーマ用：テーマのアセットを読み込む
     *
     * @param  array  $files  読み込むファイルの配列（例: ['css/style.css', 'js/app.js']）
     * @param  string|null  $themeDirectory  テーマディレクトリ名（nullの場合はアクティブテーマ）
     */
    function load_theme_assets(array $files, ?string $themeDirectory = null): string
    {
        $output = '';
        $themeDirectory = $themeDirectory ?? get_active_theme_directory();

        if (is_vite_dev_server()) {
            $viteFiles = array_map(
                fn ($file) => "themes/{$themeDirectory}/resources/src/front/{$file}",
                $files
            );
            $output .= render_vite_assets($viteFiles, false, false);
        } else {
            $themeAssetPath = "assets/themes/{$themeDirectory}";
            $manifestPath = public_path("{$themeAssetPath}/manifest.json");

            if (file_exists($manifestPath)) {
                $alternativeKeys = array_map(
                    fn ($file) => "themes/{$themeDirectory}/resources/src/front/{$file}",
                    $files
                );
                $output .= load_assets_from_manifest(
                    $manifestPath,
                    $themeAssetPath.'/',
                    $files,
                    'resources/src/front',
                    $alternativeKeys
                );
            } else {
                // マニフェストがない場合は直接ファイルを読み込む
                $output .= load_static_assets($themeAssetPath, $files);
            }
        }

        return $output;
    }
}

if (! function_exists('load_plugin_assets')) {
    /**
     * プラグイン用：プラグインのアセットを読み込む
     *
     * @param  string  $pluginDirectory  プラグインディレクトリ名
     * @param  array  $files  読み込むファイルの配列
     */
    function load_plugin_assets(string $pluginDirectory, array $files): string
    {
        $output = '';

        if (is_vite_dev_server()) {
            $viteFiles = array_map(
                fn ($file) => "plugins/{$pluginDirectory}/resources/src/{$file}",
                $files
            );
            $output .= render_vite_assets($viteFiles, false, false);
        } else {
            $pluginAssetPath = "assets/plugins/{$pluginDirectory}";
            $manifestPath = public_path("{$pluginAssetPath}/manifest.json");

            if (file_exists($manifestPath)) {
                $output .= load_assets_from_manifest(
                    $manifestPath,
                    $pluginAssetPath.'/',
                    $files,
                    "plugins/{$pluginDirectory}/resources/src"
                );
            } else {
                $output .= load_static_assets($pluginAssetPath, $files);
            }
        }

        return $output;
    }
}

if (! function_exists('load_core_assets')) {
    /**
     * コア用：コアのアセットを読み込む（Tailwind等）
     *
     * @param  array  $files  読み込むファイルの配列
     * @param  string  $type  アセットタイプ ('common', 'admin', 'front')
     */
    function load_core_assets(array $files, string $type = 'common'): string
    {
        $output = '';
        $basePath = "resources/src/{$type}";

        if (is_vite_dev_server()) {
            $viteFiles = array_map(fn ($file) => "{$basePath}/{$file}", $files);
            $output .= render_vite_assets($viteFiles, false, false);
        } else {
            // コアのビルド済みCSSを読み込む
            $coreCssFiles = [
                'assets/build/css/common_css.css',
            ];
            foreach ($coreCssFiles as $cssFile) {
                if (file_exists(public_path($cssFile))) {
                    $output .= render_css_link(asset($cssFile));
                }
            }

            // manifest.jsonからアセットを読み込む
            $output .= load_assets_from_manifest(
                public_path('assets/build/manifest.json'),
                'assets/build/',
                $files,
                $basePath
            );
        }

        return $output;
    }
}

if (! function_exists('load_front_assets')) {
    /**
     * フロントページ用：コアとテーマのアセットを読み込む
     * テーマセーフモード有効時はテーマアセットをスキップ
     *
     * @param  array  $coreFiles  コアのフロント用アセットファイル
     * @param  array  $themeFiles  テーマ固有のアセットファイル
     */
    function load_front_assets(array $coreFiles = [], array $themeFiles = []): string
    {
        $output = '';

        // コアのアセットを読み込む
        if (! empty($coreFiles)) {
            $output .= load_core_assets($coreFiles, 'front');
        } else {
            // coreFilesが空でもTailwindは読み込む
            if (! is_vite_dev_server()) {
                $coreCssFile = 'assets/build/css/common_css.css';
                if (file_exists(public_path($coreCssFile))) {
                    $output .= render_css_link(asset($coreCssFile));
                }
            }
        }

        // テーマセーフモード時はテーマアセットをスキップ
        if (! session('safe_mode_theme') && ! empty($themeFiles)) {
            $output .= load_theme_assets($themeFiles);
        }

        // x-cloak用スタイルを追加
        $output .= render_x_cloak_style();

        return $output;
    }
}

if (! function_exists('load_auth_assets')) {
    /**
     * 認証画面用：共通アセットのみを読み込む（管理画面、テーマ、プラグインのアセットは含まない）
     */
    function load_auth_assets(): string
    {
        $output = '';

        // 共通アセット（Alpine.js、Tailwind CSS等）を読み込み
        $output .= load_assets('common', null, ['js/app.js', 'scss/style.scss']);

        // 管理画面アセット（ログインフロー等）を読み込み
        $output .= load_assets('admin', null, ['js/app.js']);

        // ダークモードスクリプトを読み込み（CSP対応）
        if (is_vite_dev_server()) {
            $output .= render_vite_assets(['resources/src/auth/js/dark-mode.js'], false, false);
        } else {
            $manifestPath = public_path('assets/build/manifest.json');
            $assetBasePath = 'assets/build/';
            $output .= load_assets_from_manifest(
                $manifestPath,
                $assetBasePath,
                ['js/dark-mode.js'],
                'resources/src/auth'
            );
        }

        // x-cloak用スタイルを追加
        $output .= render_x_cloak_style();

        return $output;
    }
}

if (! function_exists('load_mail_verification_assets')) {
    /**
     * メール認証ページ用：Tailwind CSSとダークモードスクリプトを読み込む（厳格CSP対応）
     *
     * @param  string  $scriptType  'success' or 'error'
     */
    function load_mail_verification_assets(string $scriptType = 'success'): string
    {
        $output = '';

        if (is_vite_dev_server()) {
            // 開発環境：Vite経由で読み込み（各ファイルを個別に読み込む）
            $output .= render_vite_assets(['resources/src/common/scss/style.scss'], false, false);
            $output .= render_vite_assets(['resources/src/components/mail-server/js/dark-mode.js'], false, false);
            $output .= render_vite_assets(["resources/src/components/mail-server/js/verification-{$scriptType}.js"], false, false);
        } else {
            // 本番環境：ビルド済みファイルを読み込み
            $manifestPath = public_path('assets/build/manifest.json');
            $assetBasePath = 'assets/build/';

            // Tailwind CSS
            $output .= load_assets_from_manifest(
                $manifestPath,
                $assetBasePath,
                ['scss/style.scss'],
                'resources/src/common'
            );

            // ダークモードスクリプト
            $output .= load_assets_from_manifest(
                $manifestPath,
                $assetBasePath,
                ['js/dark-mode.js'],
                'resources/src/components/mail-server'
            );

            // 認証ページ固有スクリプト
            $output .= load_assets_from_manifest(
                $manifestPath,
                $assetBasePath,
                ["js/verification-{$scriptType}.js"],
                'resources/src/components/mail-server'
            );
        }

        return $output;
    }
}
