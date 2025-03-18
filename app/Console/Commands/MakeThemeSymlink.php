<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeThemeSymlink extends Command
{
    /**
     * コマンドの名前 (`php artisan theme:link` で実行)
     *
     * @var string
     */
    protected $signature = 'theme:link';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'Create a symbolic link for the active theme assets directory';

    /**
     * コマンドを実行
     *
     * @return int
     */
    public function handle()
    {
        // ✅ アクティブなテーマのディレクトリをデータベースから取得
        $activeTheme = DB::table('theme_settings')
            ->join('themes', 'theme_settings.active_theme_id', '=', 'themes.id')
            ->select('themes.directory')
            ->first();

        if (!$activeTheme) {
            $this->error("アクティブなテーマが見つかりません。");
            return 1;
        }

        $themeDirectory = $activeTheme->directory;

        // ✅ シンボリックリンク作成処理 (ヘルパー関数を使用)
        try {
            update_theme_symlink($themeDirectory);
            $this->info("シンボリックリンクを作成しました: public/assets/theme -> themes/{$themeDirectory}/resources/assets");
        } catch (\Exception $e) {
            $this->error("シンボリックリンクの作成に失敗しました: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
