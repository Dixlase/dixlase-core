<?php

/**
 * This file is part of MySoftware.
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

namespace App\Console\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;
use App\Console\Traits\MakeLicenseTrait;

/**
 * どんな「ファイル作成」コマンドにも共通する基礎ロジックをまとめる Trait
 */
trait MakeFileTrait
{
    
    use MakeLicenseTrait;

    /**
     * 共通のコマンドオプションを取得
     * 
     * @return array
     */
    protected function getCommonOptions(): array
    {
        return [
            '{--force : ' . __('command.make.common.force') . '}',
        ];
    }

    /**
     * クラス名の入力を求める
     *
     * @param string $prompt プロンプトメッセージ
     * @param string $error エラーメッセージ
     * @return string|false 入力されたクラス名、またはエラー時はfalse
     */
    protected function askForClassName(string $prompt = null, string $error = null)
    {
        $prompt = $prompt ?? __('command.class.enter_class_name');
        $error = $error ?? __('command.class.class_name_required');

        $className = $this->ask($prompt);
        if (empty($className)) {
            $this->error($error);
            return false;
        }

        return $className;
    }
    
    /**
     * コマンドのシグネチャを生成
     *
     * @param string $command コマンド名 (e.g., 'make:custom:model')
     * @param array $options オプションの配列
     * @return string
     */
    protected function makeSignature(string $command, array $options): string
    {
        // 共通オプションとマージする前に、重複するオプションを除外
        $uniqueOptions = [];
        $optionNames = [];
        
        // 共通オプションを追加
        foreach ($this->getCommonOptions() as $option) {
            $name = static::getOptionName($option);
            $optionNames[] = $name;
            $uniqueOptions[] = $option;
        }
        
        // 追加オプションを追加（重複していないもののみ）
        foreach ($options as $option) {
            $name = static::getOptionName($option);
            if (!in_array($name, $optionNames)) {
                $optionNames[] = $name;
                $uniqueOptions[] = $option;
            }
        }
        
        return $command . "\n        " . implode("\n        ", $uniqueOptions);
    }

    
    /**
     * オプション文字列からオプション名を抽出
     */
    protected static function getOptionName(string $option): string
    {
        // 引数の場合 (例: {name} または {name : description})
        if (preg_match('/^\{([^\s:]+)/', $option, $matches)) {
            return $matches[1];
        }
        // オプションの場合 (例: --option または --option= または --option=*)
        if (preg_match('/^\-\-([^\s=]+)/', $option, $matches)) {
            return $matches[1];
        }
        return $option; // マッチしない場合はそのまま返す
    }

    /**
     * 実際にファイルを作成するメイン処理。
     *
     * @param  string  $className   作成するクラス名 (e.g. "MyClass")
     * @param  array   $subDirs     サブディレクトリ ["Admin", "Nested"]
     * @param  array   $options     オプション { force: bool, ... }
     * @param  string  $stubFile    選択されたスタブファイル名
     * @return void
     */
    protected function makeFiler(
        string $className,      //クラス名
        string $fileType,       //プラグイン用かカスタムファイル用か
        string $fileCategory,   //ファイルの種類（コントローラ、リポジトリ、サービスなど）
        array $options,         //オプション
        array $subDirs,         //サブディレクトリ
        string $stub,           //スタブファイルの内容
        string $pluginName = '',//プラグイン名
        array $placeholders = [],
        array $licenseInfo = [],
    ): void {


        $scope = $options['scope'] ?? 'plain'; // スコープの取得（例: admin, front, plain）

        // 1. 名前空間とパスの生成
        $pathInfo = $this->getBaseNamespaceAndPath($fileType, $pluginName, $fileCategory, $scope, $subDirs);
        $this->namespace = $pathInfo['full_namespace'];
        $this->path = base_path($pathInfo['full_path']);

        //ライセンス情報を生成
        if (!empty($licenseInfo['template']) || !empty($licenseInfo['info'])) {
           
            $license = $this->replacePlaceholders($licenseInfo['template'], $licenseInfo['info']);

            //ライセンス情報をファイルフォーマットによって整形
            if ($fileCategory == 'blade') { //BladeファイルならBlade用の整形
            } else { //PHPならPHP用の整形
                $license = $this->embedLicenseForPhp($license);
            }
        } else {
            // 空のままにしておく
            $license = '';
        }

        // 2. プレースホルダの生成
        $defaultPlaceholders = [
            'namespace'     => $this->namespace,
            'class'         => $className,
            'rootNamespace'     => app()->getNamespace(),
            'license'           => $license,
        ];

        // 追加の置換をマージ
        $finalPlaceholders = array_merge($defaultPlaceholders, $placeholders);

        // 3. ファイル内容生成
        $content = $this->getStubContent($stub, $finalPlaceholders);

        // 4. パスとファイル名の生成
        $fullPath = $this->path . '/' . $className . '.php';

        // 5. 上書き確認
        if (File::exists($fullPath) && empty($options['force'])) {
            $this->warn(__('command.file.already_exists', ['path' => $fullPath]));
            return;
        }

        // 6. ディレクトリ作成
        if (!File::isDirectory($this->path)) {
            File::makeDirectory($this->path, 0755, true);
        }

        // 7. ファイル生成
        File::put($fullPath, $content);
        $this->info(__('command.file.created', ['path' => $fullPath]));
    }



    /**
     * ディレクトリを作成
     */

    protected function makeDirectory(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * ファイル作成後の通知
     */

    protected function notifyFileCreated(string $fileCategory, string $fullPath): void
    {
        // ファイルの種類に応じたラベルを取得
        $label = __('command.files.category.' . strtolower($fileCategory));
        $label .= __('command.files.created');
        $this->info("{$label}: {$fullPath}");
    }


    /**
     * プレースホルダを置換
     */
    protected function replacePlaceholders(string $stub, array $placeholders): string
    {

        foreach ($placeholders as $key => $value) {
            $stub = str_replace("{{ {$key} }}", $value, $stub);
        }
        return $stub;
    }


    /**
     * テキストの無駄な空白を削除
     */
    protected function trimAndIndent(string $text, int $indentLevel = 1): string
    {
        $text = trim($text);
        if ($text === '') return '';

        // 空白のみの行を空行に変換し、すべての行を取得
        $lines = collect(preg_split('/\R/u', $text))
            ->map(fn($line) => trim($line) === '' ? '' : $line);

        // 空行が2回以上続くのを防ぐ（1回だけ許可）
        $result = [];
        $blankStreak = 0;

        foreach ($lines as $line) {
            if ($line === '') {
                $blankStreak++;
                if ($blankStreak > 1) {
                    continue; // 2回目以降の空行は無視
                }
            } else {
                $blankStreak = 0;
            }
            $result[] = $line;
        }

        return implode("\n", $result);
    }

    /**
     * スタブファイルを取得
     * 
     * @param string $stub スタブファイル名（拡張子付き）
     * @param array $placeholders 置換用のプレースホルダ
     * @return string 処理済みのスタブコンテンツ
     */
    public function getStubContent(string $stub, array $placeholders = []): string
    {
        // スタブファイルのパスを構築
        $stubPath = config('command.custom_stub_directory') . '/' . $stub;
        
        // ファイルが存在するか確認
        if (!file_exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }
        
        // ファイルの内容を読み込む
        $content = file_get_contents($stubPath);
        
        // プレースホルダを置換
        $content = $this->replacePlaceholders($content, $placeholders);
        
        // インデントを整えて返す
        return $this->trimAndIndent($content);
    }

    /**
     * ファイルの種類ごとに適切な命名規則を適用
     */

    private function determineFileName(string $className, string $fileType): string
    {
        $timestamp = date('Y_m_d_His');

        // 設定から命名規則を取得（デフォルトは StudlyCase）
        $namingConvention = config("custom.file_types.{$fileType}.naming_convention", 'studly_case');

        return match ($namingConvention) {
            'snake_case' => Str::snake($className),
            'snake_case_with_timestamp' => "{$timestamp}_" . Str::snake($className),
            'kebab_case' => Str::kebab($className),
            default => Str::studly($className), // デフォルトはキャメルケース
        };
    }

    /**
     * ファイルの種類を選択
     */

    protected function chooseFileType(): array
    {
        $labels = __('command.file_type.labels');
        $prompt = __('command.file_type.prompt');
        $labelValues = array_values($labels);
        
        // 表示用の選択肢をで作成。1から始まる連番
        $displayChoices = [];
        foreach ($labelValues as $index => $label) {
            $displayChoices[$index + 1] = $label;
        }
        
        // ユーザーに選択を促す（表示は1から始まる）
        $selectedLabel = $this->choice($prompt, $displayChoices);
        
        // 選択されたラベルからファイルタイプを取得
        $fileType = array_search($selectedLabel, $labels) ?: 'core';

        $pluginName = '';
        if ($fileType === 'plugin') {
            $pluginName = $this->choosePlugin();
            if (!$pluginName) {
                return [null, null];
            }
        }

        return [$fileType, $pluginName];
    }


    /**
     * プラグイン名を選択
     */

    protected function choosePlugin(): ?string
    {
        $pluginDirs = $this->getAvailablePluginNames();

        if (empty($pluginDirs)) {
            $this->error(__('command.plugin.not_found'));
            return null;
        }

        // 表示用の選択肢を1から始まる連番で作成
        $displayChoices = [];
        foreach ($pluginDirs as $index => $plugin) {
            $displayChoices[$index + 1] = $plugin;
        }

        // ユーザーに選択を促す（表示は1から始まる）
        return $this->choice(__('command.plugin.prompt'), $displayChoices);
    }

    /**
     * プラグインの一覧を取得
     */
    protected function getAvailablePluginNames(): array
    {
        return collect(File::directories(base_path('plugins')))
            ->map(fn($dir) => basename($dir))
            ->filter()
            ->values()
            ->all();
    }


    /**
     * スコープを選択
     */
    protected function chooseScope(): string
    {
        $labels = __('command.scope.labels'); // 日本語 or 英語
        $map    = __('command.scope.map');    // 'plain' => 'スコープなし' など
        $labelValues = array_values($labels);
        
        // 表示用の選択肢を1から始まる連番で作成
        $displayChoices = [];
        foreach ($labelValues as $index => $label) {
            $displayChoices[$index + 1] = $label;
        }
        
        // ユーザーに選択を促す（表示は1から始まる）
        $selectedLabel = $this->choice(__('command.scope.prompt'), $displayChoices);
        
        // 選択されたラベルからスコープを取得
        return array_search($selectedLabel, $labels) ?: 'plain';
    }

    /**
     * クラス名のパス情報を分解
     *
     * @param string $category ファイルカテゴリ（'controller', 'model', 'blade' など）
     * @param string $classPath クラスパス（例: 'Admin/User/Controller'）
     * @param string $scope スコープ（例: 'Admin'）
     * @return array [className, subDirs]
     */
    protected function parseClassPath(string $category, string $classPath, string $scope = null): array
    {
        $path = str_replace('\\', '/', $classPath);
        $parts = explode('/', $path);
        $className = array_pop($parts);
        
        // カテゴリに基づいてディレクトリ名のケースを調整
        $subDirs = array_map(function($part) use ($category) {
            // 設定で指定されたカテゴリはStudlyCase、それ以外は小文字
            if (in_array($category, config('command.studly_case_categories', []))) {
                return Str::studly($part);
            }
            // それ以外（blade, migration, factory, seeder, config, route, langなど）はすべて小文字
            return strtolower($part);
        }, $parts);

        // スコープが指定されている場合は適用
        if ($scope) {
            $subDirs = $this->applyScopeToSubDirs($subDirs, $scope, $category);
        }
        
        return [$className, $subDirs];
    }

    /**
     * カテゴリに基づいてオプションをマージ
     *
     * @param string $category カテゴリ名
     * @param array $common 共通オプション配列
     * @param array $options 現在のオプション配列
     * @return array マージされたオプション配列
     */
    protected function mergeCategoryOptions(string $category, array $common, array $options): array
    {
        // カテゴリに基づいて適切なオプションを追加
        if ($category === 'route' && isset($common['routeType'])) {
            // ルートファイルの場合はルートタイプをマージ
            $options = array_merge($options, ['routeType' => $common['routeType']]);
        } elseif ($category === 'lang' && isset($common['lang'])) {
            // 言語ファイルの場合は言語をマージ
            $options = array_merge($options, ['lang' => $common['lang']]);
        } elseif ($category === 'config') {
            // コンフィグファイルの場合はスコープを'plain'に設定
            $options = array_merge($options, ['scope' => 'plain']);
        } elseif ($category === 'provider') {
            // プロバイダーファイルの場合はスコープを'plain'に設定
            $options = array_merge($options, ['scope' => 'plain']);
        } elseif ($category === 'controller' && isset($common['scope'])) {
            // コントローラーファイルの場合はスコープをマージ
            $options = array_merge($options, ['scope' => $common['scope']]);
        } elseif ($category === 'model') {
            // モデルファイルの場合はapp/Modelsディレクトリに作成するように設定
            //$options = array_merge($options, ['target_directory' => 'Models']);
            //if (isset($common['scope'])) {
            //    $options = array_merge($options, ['scope' => $common['scope']]);
            //}
        } elseif (isset($common['scope'])) {
            // その他の場合はスコープをマージ
            $options = array_merge($options, ['scope' => $common['scope']]);
        }

        return $options;
    }

    /**
     * スコープをサブディレクトリに適用
     *
     * @param array $subDirs サブディレクトリの配列
     * @param string $scope 適用するスコープ
     * @param string $fileCategory ファイルカテゴリ（オプション）
     * @return array スコープが適用されたサブディレクトリの配列
     */
    protected function applyScopeToSubDirs(array $subDirs, string $scope, string $fileCategory = ''): array
    {
        // スコープが'plain'の場合は何も追加しない
        if ($scope === 'plain') {
            return $subDirs;
        }
        
        // スコープを適切なケースに変換
        $formattedScope = $this->formatScopeForCategory($scope, $fileCategory);
        
        // 既に先頭が同じスコープの場合は何もしない
        if (!empty($subDirs) && $subDirs[0] === $formattedScope) {
            return $subDirs;
        }
        
        // 先頭にスコープを追加
        return [$formattedScope, ...$subDirs];
    }
    
    /**
     * ファイルカテゴリに基づいてスコープを適切なケースに変換
     *
     * @param string $scope スコープ名
     * @param string $fileCategory ファイルカテゴリ
     * @return string フォーマットされたスコープ名
     */
    protected function formatScopeForCategory(string $scope, string $fileCategory = ''): string
    {
        // スコープが'plain'の場合は空文字を返す
        if ($scope === 'plain') {
            return '';
        }
        
        // Bladeファイルの場合はすべて小文字
        if ($fileCategory === 'blade') {
            return strtolower($scope);
        }
        
        // 設定で指定されたカテゴリはStudlyCase
        if (in_array($fileCategory, config('command.studly_case_categories', []))) {
            return Str::studly($scope);
        }
        
        // それ以外は頭文字を大文字に
        return ucfirst($scope);
    }

    /**
     * スコープを適用したネームスペース、パスを取得
     */
    protected function getBaseNamespaceAndPath(string $fileType, string $pluginName, string $fileCategory, string $scope, array $subDirs): array
    {
        $nameStudly = \Illuminate\Support\Str::studly($pluginName);

        // スコープを適切な形式に変換
        $scopeStudly = $this->formatScopeForCategory($scope, $fileCategory);

        $namespaceSubDir = implode('\\', $subDirs);
        $pathSubDir = implode('/', $subDirs);

        // 設定からカテゴリごとの設定を取得 [path, namespace]
        $categoryConfig = config('command.category_paths')[$fileCategory] ?? ['', ''];
        [$basePath, $baseNamespace] = array_pad($categoryConfig, 2, '');

        $pluginStudly = Str::studly($pluginName);

        // ファイルタイプに応じてベースパスとネームスペースを設定
        switch ($fileType) {
            case 'plugin':
                // プラグイン用ファイル
                $pluginStudly = Str::studly($pluginName);
                if (empty($pluginStudly)) {
                    throw new \RuntimeException('Plugin name is required');
                }
                
                if ($fileCategory === 'provider') {
                    // プロバイダーはプラグインのルートのapp/Providersに配置
                    $basePath = "plugins/{$pluginStudly}/app/Providers";
                    $baseNamespace = "Plugins\\{$pluginStudly}\\App\\Providers";
                } else {
                    $basePath = "plugins/{$pluginStudly}/{$basePath}";
                    $baseNamespace = $baseNamespace 
                        ? "Plugins\\{$pluginStudly}\\{$baseNamespace}" 
                        : "Plugins\\{$pluginStudly}";
                }
                break;

            case 'custom_core':
                // コア用カスタムファイル
                $basePath = 'custom/' . $basePath;
                $baseNamespace = $baseNamespace 
                    ? 'Custom\\' . $baseNamespace 
                    : 'Custom';
                break;

            case 'custom_plugin':
                // プラグイン用カスタムファイル
                $basePath = "custom/plugins/{$pluginStudly}/{$basePath}";
                $baseNamespace = $baseNamespace 
                    ? "Custom\\Plugins\\{$pluginStudly}\\{$baseNamespace}" 
                    : "Custom\\Plugins\\{$pluginStudly}";
                break;

            default:
                $basePath = $basePath ?: 'app';
                $baseNamespace = $baseNamespace ?: 'App';
        }

        // サブディレクトリを追加
        if ($pathSubDir) {
            $basePath = rtrim($basePath, '/') . '/' . $pathSubDir;
        }
        if ($namespaceSubDir) {
            $baseNamespace = rtrim($baseNamespace, '\\') . '\\' . $namespaceSubDir;
        }

        // 先頭のバックスラッシュを削除
        $baseNamespace = ltrim($baseNamespace, '\\');

        return [
            'full_path' => $basePath,
            'full_namespace' => $baseNamespace,
            'base_path' => dirname($basePath) === '.' ? '' : dirname($basePath),
            'base_namespace' => $baseNamespace ? '\\' . $baseNamespace : '',
            'sub_dir' => $pathSubDir,
            'sub_namespace' => $namespaceSubDir,
            'file_category' => $fileCategory,
        ];
    }




    /**
     * 関連ファイルの生成
     *
     * @param string $className クラス名
     * @param string $fileType ファイルタイプ
     * @param array $subDirs サブディレクトリ
     * @param string $pluginName プラグイン名
     */
    protected function createRelatedFiles(string $className, string $fileType, array $subDirs, string $pluginName): void
    {
        if ($this->hasOption('factory') && $this->option('factory')) {
            $this->createFactory($className, $fileType, $subDirs, $pluginName);
        }

        if ($this->hasOption('migration') && $this->option('migration')) {
            $this->createMigration($className, $fileType, $pluginName);
        }

        if ($this->hasOption('controller') && $this->option('controller')) {
            $this->createController($className, $fileType, $subDirs, $pluginName);
        }
    }


}
