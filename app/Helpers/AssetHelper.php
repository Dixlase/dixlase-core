<?php

if (!function_exists('asset_url')) {
    /**
     * アセットURLを生成する
     *
     * @param string $type アセットの種類（admin, theme, pluginなど）
     * @param string $path アセットファイルのパス
     * @return string 完全なURL
     */
    function asset_url(string $type, string $path): string
    {
        switch ($type) {
            case 'theme':
                return url("assets/theme/{$path}");
            case (preg_match('/^plugins\/(.+)$/', $type, $matches) ? true : false):
                $pluginName = $matches[1];
                return url("assets/plugins/{$pluginName}/{$path}");
            default:
                abort(404, "Invalid asset type: {$type}");
        }
    }
}

if (!function_exists('active_theme_directory')) {
    /**
     * 現在有効化されているテーマのディレクトリ名を取得
     *
     * @return string アクティブテーマのディレクトリ名
     */
    function active_theme_directory(): string
    {
        $activeThemeId = DB::table('settings_theme')->value('active_theme_id');
        $theme = \App\Models\Theme::find($activeThemeId);
        return $theme ? $theme->directory : 'default-theme';
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
        $target = base_path("themes/{$themeDirectory}/assets");
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
            throw new \Exception("Assets directory does not exist for theme: {$target}");
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
        $target = base_path("plugins/{$pluginDirectory}/assets");
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
            throw new \Exception("Assets directory does not exist for plugin: {$target}");
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
