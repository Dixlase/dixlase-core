<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeBladeTrait;

class MakeThemeBlade extends Command
{
    use MakeBladeTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:theme:blade
        {theme : The theme name (e.g. MyTheme)}
        {file : The blade file name (e.g. admin/dashboard)}
        {--force : Overwrite if the blade file already exists}
        {--type=front : The type of Blade file (front/admin)}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // テーマ名
        $theme = Str::studly($this->argument('theme'));
        // 作成したいファイル (e.g. "admin/media/upload")
        $file = $this->argument('file');

        // オプション
        $options = [
            'force' => (bool) $this->option('force'),
            'type'  => $this->option('type'),
        ];

        // front / admin チェック
        if (! in_array($options['type'], ['front', 'admin'])) {
            $this->error("Invalid type: '{$options['type']}'. Choose 'front' or 'admin'.");
            return 1;
        }

        // Bladeファイルを作成
        // 第2引数には空配列を渡し、MakeBladeTrait でサブディレクトリ解析
        $this->makeFile($file, [], $options);

        return 0;
    }

    /**
     * テーマ用 => "themes/{Theme}/resources/views"
     */
    protected function getDirectory(array $subDirs): string
    {
        // コマンド引数のテーマ名
        $themeName = Str::studly($this->argument('theme'));

        // 基本ディレクトリ
        $base = base_path("themes/{$themeName}/resources/views");

        // サブディレクトリを結合
        if (!empty($subDirs)) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    /**
     * Blade ファイルにはネームスペースは不要なので空文字を返す
     */
    protected function getNamespace(array $subDirs): string
    {
        return '';
    }
}
