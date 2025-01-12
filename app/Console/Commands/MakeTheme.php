<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Theme;
use App\Services\FileGenerator;

class MakeTheme extends Command
{
    protected $signature = 'make:theme {name} {--install} {--activate}';
    protected $description = 'Create a new theme, optionally register it in the database and activate it';

    protected FileGenerator $fileGenerator;

    /**
     * コンストラクタ
     */
    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // ユーザーが入力したテーマ名（スペース等を含むオリジナル）
        $originalName = $this->argument('name');

        // テーマ用ディレクトリ名 (ケバブケース化)
        $themeDirName = Str::kebab($originalName);

        // slug化はFileGeneratorへ委譲
        $slugName = $this->fileGenerator->sanitizeName($originalName);

        // テーマ保存先のパス
        $themeDir = base_path("themes/{$themeDirName}");


        // 既に存在していたらエラー
        if (File::exists($themeDir)) {
            $this->error("Theme '{$originalName}' already exists.");
            return Command::FAILURE;
        }

        // テーマディレクトリ作成
        $this->createThemeDirectories($themeDir);

        // テーマ初期ファイルの生成
        $this->createThemeFiles($themeDir, $originalName, $themeDirName);

        // テーマをデータベースに登録
        if ($this->option('install')) {
            $themeId = $this->registerThemeInDatabase($originalName, $themeDirName);

            // テーマを有効化
            if ($this->option('activate')) {
                $this->activateTheme($themeId, $themeDirName);
            }
        }

        $this->info("Theme '{$originalName}' has been created successfully.");
        return Command::SUCCESS;
    }



    /**
     * テーマディレクトリと初期ファイルを作成
     *
     * @param string $directory
     * @param string $themeName
     */
    protected function createThemeDirectories(string $themeDir): void
    {
        $directories = [
            'resources/views',
            'resources/src/js',
            'resources/src/css',
            'resources/assets/js',
            'resources/assets/css',
            'resources/assets/images',
        ];
        // ルートディレクトリの作成
        File::makeDirectory($themeDir, 0755, true);

        // 各サブディレクトリを作成
        foreach ($directories as $dir) {
            File::makeDirectory("{$themeDir}/{$dir}", 0755, true);
        }
    }

    /**
     * テーマ用のファイルをスタブベースで作成
     *
     * @param string $themeDir      テーマディレクトリのパス
     * @param string $themeName     ユーザーが入力したテーマの人間向け名称
     * @param string $themeDirName  テーマのディレクトリ名(ケバブケース)
     */
    protected function createThemeFiles(string $themeDir, string $themeName, string $themeDirName): void
    {
        // スタブファイルを探すパス
        $stubPath = [base_path('stubs')];

        // 外部ファイルやDBなどからライセンス情報を取得
        $licenseContent = $this->fileGenerator->getLicenseContent();

        // プレースホルダ定義
        $placeholders = [
            '{{ license }}'        => $licenseContent,
            '{{ themeName }}'      => $themeName,      // 人間向け名称
            '{{ themeDirectory }}' => $themeDirName,   // ディレクトリ名
        ];

        // ***** vite.config.js *****
        $stubFile = $this->fileGenerator->getStubContent('vite.config.theme.stub', null, $stubPath);
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$themeDir}/vite.config.js", $fileContent);

        // ***** composer.json *****
        $stubFile = $this->fileGenerator->getStubContent('composer.theme.stub', null, $stubPath);
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$themeDir}/composer.json", $fileContent);

        // ***** index.blade.php *****
        // スタブを使わずに直接生成する例 (必要ならstubs化してもOK)
        $bladeContent = "<h1>Welcome to {$themeName} Theme</h1>";
        File::put("{$themeDir}/resources/views/index.blade.php", $bladeContent);

        // ***** デフォルトJS/SCSSなどの初期ファイル *****
        File::put("{$themeDir}/resources/src/js/app.js", "// JavaScript for {$themeDirName}");
        File::put("{$themeDir}/resources/src/css/style.css", "/* Styles for {$themeDirName} */");
    }


    /**
     * テーマをデータベースに登録
     *
     * @param string $originalName
     * @param string $themeName
     * @return int $themeId
     */
    protected function registerThemeInDatabase(string $originalName, string $themeName)
    {
        if (Theme::where('slug', $themeName)->exists()) {
            $this->error("Theme '{$themeName}' is already registered in the database.");
            return Command::FAILURE;
        }

        $theme = Theme::create([
            'name' => $originalName,
            'slug' => $themeName,
            'directory' => $themeName,
            'version' => '1.0.0',
        ]);

        $this->info("Theme '{$themeName}' has been registered in the database.");
        return $theme->id;
    }

    /**
     * テーマを有効化
     *
     * @param int $themeId
     */
    protected function activateTheme(int $themeId, string $themeName)
    {
        DB::table('settings_theme')->updateOrInsert(
            ['id' => 1], // 一意の設定
            ['active_theme_id' => $themeId, 'updated_at' => now()]
        );

        $themeAssetsDir = base_path("themes/{$themeName}/resources/assets");
        $linkDir = public_path("assets/theme");

        // シンボリックリンクの作成
        if (File::exists($themeAssetsDir) && is_dir($themeAssetsDir)) {
            // すでにリンクがあれば削除
            if (File::exists($linkDir) || is_link($linkDir)) {
                unlink($linkDir);
            }

            // 新しくシンボリックリンクを作成
            symlink($themeAssetsDir, $linkDir);
            $this->info("Symlink created: {$linkDir} -> {$themeAssetsDir}");
        } else {
            $this->warn("Assets directory does not exist for theme: {$themeName}");
        }

        $this->info("Theme ID '{$themeId}' has been activated.");
    }
}
