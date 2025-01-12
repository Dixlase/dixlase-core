<?php

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
        } else {
            // 本番環境: manifest.jsonを解析
            $manifestPath = match ($type) {
                'common' => public_path("assets/common/manifest.json"),
                'admin' => public_path("assets/admin/manifest.json"),
                'theme' => public_path("assets/theme/manifest.json"),
                'plugin' => public_path("assets/plugins/{$name}/manifest.json"),
            };



            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);

                if (!empty($manifest)) {
                    // JSファイル
                    foreach ($manifest as $key => $entry) {

                        if (isset($entry['file'])) {
                            $output .= '<script type="module" src="' . asset("assets/{$type}/" . ($type === 'plugin' || $type === 'theme' ? "{$name}/" : '') . $entry['file']) . '"></script>';
                        }
                    }
                    // CSSファイル
                    foreach ($manifest as $key => $entry) {
                        if (isset($entry['css'])) {
                            // CSSファイル
                            foreach ($entry['css'] as $css) {
                                $output .= '<link rel="stylesheet" href="' . asset("assets/{$type}/" . ($type === 'plugin' || $type === 'theme' ? "{$name}/" : '') . $css) . '">';
                            }
                        }
                    }

                    // フォントやその他のアセット
                    foreach ($manifest as $key => $entry) {
                        if (isset($entry['assets'])) {

                            foreach ($entry['assets'] as $asset) {
                                $output .= '<link rel="preload" as="font" href="' . asset("assets/{$type}/" . ($type === 'plugin' || $type === 'theme' ? "{$name}/" : '') . $asset) . '" type="font/' . pathinfo($asset, PATHINFO_EXTENSION) . '" crossorigin="anonymous">';
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
                'js/admin.js',
                'scss/admin.scss',
            ]
        );

        // アクティブなテーマIDを取得
        $activeThemeId = DB::table('settings_theme')->where('active_theme_id', 1)->first();
        // アクティブなテーマを取得
        $activeTheme = \App\Models\Theme::find($activeThemeId);

        if ($activeTheme) {
            $output .= load_assets('theme', $activeTheme->directory, [
                'js/app.js',
                'scss/style.scss',
            ]);
        }

        // 有効なプラグインを取得
        $activePlugins = DB::table('plugins')->where('status', 1)->get();

        foreach ($activePlugins as $plugin) {
            $output .= load_assets('plugin', $plugin->directory, [
                'js/app.js',
                'scss/style.scss',
            ]);
        }

        return $output;
    }
}

if (!function_exists('update_theme_symlink')) {
    /**
     * アクティブテーマのシンボリックリンクを更新
     *
     * @param string $themeDirectory アクティブなテーマのディレクトリ名
     * @return void
     */
    function update_theme_symlink(string $themeDirectory)
    {
        $target = base_path("themes/{$themeDirectory}/resources/assets");
        $link = public_path('assets/theme');

        // 古いシンボリックリンクを削除
        if (file_exists($link) || is_link($link)) {
            unlink($link);
        }

        // 新しいシンボリックリンクを作成
        if (file_exists($target) && is_dir($target)) {
            // assetsフォルダがある場合はシンボリックリンクを作成
            symlink($target, $link);
        } else {
            // assetsフォルダがない場合はシンボリックリンクを作成しない
            //$this->info("Assets directory does not exist for theme: {$target}");
            //throw new \Exception("Assets directory does not exist for theme: {$target}");
        }

        symlink($target, $link);
    }
}


if (!function_exists('create_plugin_symlink')) {
    /**
     * プラグインのアセット用シンボリックリンクを作成
     *
     * @param string $pluginDirectory プラグインのディレクトリ名
     * @return void
     */
    function create_plugin_symlink(string $pluginDirectory)
    {
        $target = base_path("plugins/{$pluginDirectory}/resources/assets");
        $link = public_path("assets/plugins/{$pluginDirectory}");

        if (file_exists($target) && is_dir($target)) {
            // 古いシンボリックリンクを削除
            if (file_exists($link) || is_link($link)) {
                unlink($link);
            }
            // 新しいシンボリックリンクを作成
            symlink($target, $link);
        } else {
            // assetsフォルダがない場合はシンボリックリンクを作成しない
            //$this->info("Assets directory does not exist for plugin: {$target}");
            //throw new \Exception("Assets directory does not exist for plugin: {$target}");
        }
    }
}

if (!function_exists('delete_plugin_symlink')) {
    /**
     * プラグインのアセット用シンボリックリンクを削除
     *
     * @param string $pluginDirectory プラグインのディレクトリ名
     * @return void
     */
    function delete_plugin_symlink(string $pluginDirectory)
    {
        $link = public_path("assets/plugins/{$pluginDirectory}");

        // シンボリックリンクを削除
        if (file_exists($link) || is_link($link)) {
            unlink($link);
        }
    }
}
