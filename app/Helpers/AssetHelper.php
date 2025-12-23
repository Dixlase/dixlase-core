<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

if (!function_exists('load_assets')) {
    /**
     * アセットを動的に読み込む関数
     *
     * @param string $type アセットのタイプ ('common', 'admin', 'theme', 'plugin')
     * @param string|null $name テーマまたはプラグインの場合のディレクトリ名 (common, admin では null)
     * @param array $files 読み込むJSまたはCSSファイルのリスト
     * @return string
     */
    function load_assets(string $type, ?string $name, array $files)
    {
        $output = '';
        $basePath = match ($type) {
            'common' => 'resources/src/common',
            'admin' => 'resources/src/admin',
            'theme' => "themes/{$name}/resources/src",
            'plugin' => "plugins/{$name}/resources/src",
            default => throw new InvalidArgumentException("Invalid type: {$type}"),
        };

        if (app()->environment('local')) {
            // ローカル環境: Viteを使用
            $viteFiles = array_map(fn($file) => "{$basePath}/{$file}", $files);
            $output .= \Illuminate\Support\Facades\Blade::render('@vite(' . implode(', ', array_map(fn($file) => "'{$file}'", $viteFiles)) . ')');
            // （Bladeのstyleタグでx-cloakを追加）
            $output .= '<style>[x-cloak]{display:none!important;}</style>';
            // FOUCを防ぐためのスタイル（bodyを一瞬非表示）
            $output .= '<style>body{opacity:0;visibility:hidden;}</style>';
            // CSSがロードされたらbodyを表示させるスクリプト
            $output .= <<<HTML
<script>
    window.addEventListener('load', () => {
        document.body.style.visibility = 'visible';
        document.body.style.opacity = '1';
    });
</script>
HTML;
        } else {
            // 本番/ステージング環境: manifest.jsonを解析
            // コアアセット（common, admin）は統合されたmanifest.jsonを使用
            $manifestPath = match ($type) {
                'common', 'admin' => public_path("assets/build/manifest.json"),
                'theme' => public_path("assets/themes/{$name}/manifest.json"),
                'plugin' => public_path("assets/plugins/{$name}/manifest.json"),
            };

            // コアアセットのベースパス
            $assetBasePath = match ($type) {
                'common', 'admin' => 'assets/build/',
                'theme' => "assets/themes/{$name}/",
                'plugin' => "assets/plugins/{$name}/",
            };

            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);

                if (!empty($manifest)) {
                    // 指定されたファイルに対応するエントリを探す
                    foreach ($files as $file) {
                        $manifestKey = "{$basePath}/{$file}";
                        
                        if (isset($manifest[$manifestKey])) {
                            $entry = $manifest[$manifestKey];
                            
                            // JSファイル
                            if (isset($entry['file']) && str_ends_with($entry['file'], '.js')) {
                                $nonce = function_exists('csp_nonce_attr') ? ' ' . csp_nonce_attr() : '';
                                $output .= '<script type="module" src="' . asset($assetBasePath . $entry['file']) . '"' . $nonce . '></script>';
                            }
                            
                            // CSSファイル（エントリ自体がCSSの場合）
                            if (isset($entry['file']) && str_ends_with($entry['file'], '.css')) {
                                $output .= '<link rel="stylesheet" href="' . asset($assetBasePath . $entry['file']) . '">';
                            }
                            
                            // CSSファイル（JSエントリに紐づくCSS）
                            if (isset($entry['css'])) {
                                foreach ($entry['css'] as $css) {
                                    $output .= '<link rel="stylesheet" href="' . asset($assetBasePath . $css) . '">';
                                }
                            }

                            // フォントやその他のアセット
                            if (isset($entry['assets'])) {
                                foreach ($entry['assets'] as $asset) {
                                    $ext = pathinfo($asset, PATHINFO_EXTENSION);
                                    if (in_array($ext, ['woff', 'woff2', 'ttf', 'otf', 'eot'])) {
                                        $output .= '<link rel="preload" as="font" href="' . asset($assetBasePath . $asset) . '" type="font/' . $ext . '" crossorigin="anonymous">';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        return $output;
    }
}

if (!function_exists('load_active_assets')) {
    /**
     * アクティブなテーマとプラグインのアセットをBladeヘッダーに追加
     *
     * @return string
     */
    function load_active_assets()
    {
        $output = '';

        //共通と管理画面用のアセットを読み込み
        $output .= load_assets(
            'common',
            null,
            [
                'js/app.js',
                'scss/style.scss',
            ]
        );

        // 管理画面用のアセットを読み込み
        $output .= load_assets(
            'admin',
            null,
            [
                'js/app.js',
                'scss/style.scss',
            ]
        );

        // アクティブなテーマIDを取得
        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();

        if ($themeSetting && $themeSetting->value) {
            $activeThemeId = (int)$themeSetting->value;
        } else {
            $activeThemeId = 1;
        }

        // アクティブなテーマを取得
        $activeTheme = \App\Models\Theme::find($activeThemeId);

        if ($activeTheme) {
            $output .= load_assets('theme', $activeTheme->directory, [
                'js/app.js',
                'scss/style.scss',
            ]);
        }

        // 有効なプラグインを取得
        $activePlugins = DB::table('plugins')->whereNotNull('enabled_at')->get();

        foreach ($activePlugins as $plugin) {
            $output .= load_assets('plugin', $plugin->directory, [
                'js/app.js',
                'scss/style.scss',
            ]);
        }

        return $output;
    }
}

