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
use App\Console\Traits\MakeLicenseTrait;


/**
 * どんな「ファイル作成」コマンドにも共通する基礎ロジックをまとめる Trait
 */
trait MakeFileTrait
{

    use MakeLicenseTrait;

    protected $namespace = '';
    protected $path = '';

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
        string $className,  //クラス名
        string $fileType, //プラグイン用かカスタムファイル用か
        string $fileCategory, //ファイルの種類（コントローラ、リポジトリ、サービスなど）
        array $options, //オプション
        array $subDirs, //サブディレクトリ
        string $stub, //スタブファイルの内容
        string $pluginName = '', //プラグイン名
        array $placeholders = [],
        array $licenseInfo = [],
    ): void {

        $scope = $options['scope'] ?? 'plain'; // スコープの取得（例: admin, front, plain）

        // 1. 名前空間とパスの生成
        [$this->namespace, $this->path] = $this->getBaseNamespaceAndPath($fileType, $pluginName, $fileCategory, $scope, $subDirs);

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
            'namespacePath'     => $this->namespace,
            'className'         => $className,
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
            $this->warn("File already exists: {$fullPath}");
            return;
        }

        // 6. ディレクトリ作成
        if (!File::isDirectory($this->path)) {
            File::makeDirectory($this->path, 0755, true);
        }

        // 7. ファイル生成
        File::put($fullPath, $content);
        $this->info("Controller created: {$fullPath}");
    }

    protected function getBaseNamespaceAndPath(string $fileType, string $pluginName, string $fileCategory, string $scope, array $subDirs): array
    {
        $nameStudly = \Illuminate\Support\Str::studly($pluginName);
        $categoryStudly = \Illuminate\Support\Str::studly($fileCategory);

        // スコープを取得（例: admin, front, plain）
        $scopeStudly = $scope !== 'plain' ? ucfirst($scope) : '';

        // スコープをサブディレクトリに追加（plain の場合は除外）
        if ($scopeStudly) {
            array_unshift($subDirs, $scopeStudly);
        }

        $namespaceSubDir = implode('\\', $subDirs);
        $pathSubDir = implode('/', $subDirs);

        $namespace = "{$fileType}\\{$nameStudly}\\App\\Http\\{$categoryStudly}" . ($namespaceSubDir ? "\\{$namespaceSubDir}" : '');
        $path = base_path(strtolower($fileType) . "/{$nameStudly}/app/Http/{$categoryStudly}" . ($pathSubDir ? "/{$pathSubDir}" : ''));

        return [$namespace, $path];
    }

    protected function makeDirectory(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * プレースホルダを置換
     */
    public function replacePlaceholders(string $template, array $placeholders): string
    {
        foreach ($placeholders as $search => $replace) {
            $stub = str_replace('{{ ' . $search . ' }}', $replace, $template);
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
     */
    public function getStubContent(string $stub, array $placeholders = []): string
    {

        $content = $this->replacePlaceholders($stub, $placeholders);
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
        $labels = __('choices.scope.labels'); // 日本語 or 英語
        $map    = __('choices.scope.map');    // 'plain' => 'スコープなし' など

        $key = $this->choice(__('choices.scope.prompt'), array_values($labels));
        return array_search($key, $labels); // reverse map
    }
}
