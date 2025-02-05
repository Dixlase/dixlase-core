<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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


namespace App\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Lang;


trait CustomFilesLoaderTrait
{


    // カスタムファイルを読み込む
    public function loadCustomFilesForType($customFilesPath, $typeConfig)
    {
        $customPath = base_path($customFilesPath . $typeConfig['path']);
        $defaultNamespace = $typeConfig['namespace'];

        $this->loadCustomFiles($customPath, $defaultNamespace);
    }

    public function loadCustomFiles($customPath, $defaultNamespace)
    {
        if (!File::exists($customPath)) {
            return;
        }

        foreach (File::allFiles($customPath) as $file) {
            $relativePath = Str::replaceFirst($customPath, '', $file->getPath());
            $className = $this->getClassNameFromPath($relativePath);

            $customClass = $defaultNamespace . 'Custom\\' . $className;
            $coreClass = $defaultNamespace . $className;

            if (class_exists($customClass)) {
                if (class_exists($coreClass)) {
                    $mergeMode = config('custom.default_merge_mode', 'merge');

                    if ($mergeMode === 'replace') {
                        App::bind($coreClass, $customClass);
                    } elseif ($mergeMode === 'merge') {
                        $mergedClass = $this->mergeClasses($coreClass, $customClass);
                        App::bind($coreClass, $mergedClass);
                    }
                } else {
                    App::bind($customClass, $customClass);
                }
            }
        }
    }

    private function getClassNameFromPath($relativePath)
    {
        return str_replace(['/', '.php'], ['\\', ''], $relativePath);
    }

    private function mergeClasses(string $coreClass, string $customClass)
    {
        // 動的なクラス生成のためのクラス名を定義
        $mergedClassName = $coreClass . 'MergedWith' . $customClass;

        if (!class_exists($mergedClassName)) {
            // 動的にクラスを生成
            eval("
            class {$mergedClassName} extends {$coreClass} {
                private \$customInstance;

                public function __construct()
                {
                    parent::__construct();
                    \$this->customInstance = new {$customClass}();
                }

                public function __call(\$method, \$args)
                {
                    if (method_exists(\$this->customInstance, \$method)) {
                        return \$this->customInstance->\$method(...\$args);
                    }
                    return parent::__call(\$method, \$args);
                }
            }
        ");
        }

        // 動的に生成したクラスのインスタンスを返す
        return new $mergedClassName();
    }



    /*

    public function loadCustomConfigs($path)
    {
        if (File::isDirectory($path)) {
            foreach (File::allFiles($path) as $file) {
                $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $config = Config::get($filename, []);

                // カスタム設定ファイルを読み込み
                $customConfig = require $file->getPathname();

                if (!is_array($customConfig)) {
                    throw new \UnexpectedValueException("Config file {$file->getPathname()} must return an array.");
                }

                // ファイル内のモードを優先し、デフォルトモードをフォールバック
                $mergeMode = $customConfig['_merge_mode'] ?? config('app.default_merge_mode', 'merge');

                if ($mergeMode === 'replace') {
                    Config::set($filename, $customConfig);
                } else {
                    Config::set($filename, array_merge_recursive($config, $customConfig));
                }
            }
        }
    }

    // カスタムルートを読み込む
    public function loadCustomRoutes($path)
    {
        if (File::isDirectory($path)) {
            foreach (File::allFiles($path) as $file) {
                // ミドルウェアの指定（必要に応じて変更可能）
                Route::middleware('web')
                    ->group($file->getPathname());
            }
        }
    }

    // カスタム言語ファイルを読み込む
    public function loadCustomLang($path)
    {
        if (File::isDirectory($path)) {
            foreach (File::directories($path) as $localePath) {
                $locale = basename($localePath);
                foreach (File::allFiles($localePath) as $file) {
                    $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                    // デフォルト翻訳を取得
                    $defaultLang = Lang::getLoader()->load($locale, $group) ?? [];

                    // カスタム翻訳を取得
                    $customLang = require $file->getPathname();

                    if (!is_array($customLang)) {
                        throw new \UnexpectedValueException("Language file {$file->getPathname()} must return an array.");
                    }

                    // ファイル内のモードを優先し、デフォルトモードをフォールバック
                    $mergeMode = $customLang['_merge_mode'] ?? config('app.default_merge_mode', 'merge');

                    if ($mergeMode === 'replace') {
                        $mergedLang = $customLang;
                    } else {
                        $mergedLang = array_merge_recursive($defaultLang, $customLang);
                    }

                    // 言語ラインを登録
                    Lang::addLines([$group => $mergedLang], $locale);
                }
            }
        }
    }

    // カスタムビューファイルを読み込む
    public function loadCustomViews($path)
    {
        if (File::exists($path)) {
            // カスタムビューを読み込み
            View::addLocation($path);
        }
    }

    // カスタムコンフィグの配列を再帰的にマージする関数
    function array_merge_recursive_custom(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->array_merge_recursive_custom($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    // カスタムコントローラを読み込む
    public function loadCustomControllers($customPath, $defaultNamespace = 'App\\Http\\Controllers\\')
    {
        if (!File::exists($customPath)) {
            return;
        }

        foreach (File::allFiles($customPath) as $file) {
            $relativePath = Str::replaceFirst($customPath, '', $file->getPath());
            $className = $this->getClassNameFromPath($relativePath);

            // カスタムクラスとコアクラスを特定
            $customClass = $defaultNamespace . 'Custom\\' . $className;
            $coreClass = $defaultNamespace . $className;

            if (class_exists($customClass)) {
                if (class_exists($coreClass)) {
                    // マージまたは置換を選択
                    $mergeMode = config('custom.default_merge_mode', 'merge');

                    if ($mergeMode === 'replace') {
                        App::bind($coreClass, $customClass);
                    } elseif ($mergeMode === 'merge') {
                        $mergedClass = $this->mergeControllers($coreClass, $customClass);
                        App::bind($coreClass, $mergedClass);
                    }
                } else {
                    // コアクラスが存在しない場合はそのままバインド
                    App::bind($customClass, $customClass);
                }
            }
        }
    }

    // カスタムコントローラのクラス名を取得
    private function getClassNameFromPath($relativePath)
    {
        return str_replace(['/', '.php'], ['\\', ''], $relativePath);
    }

    // コアクラスとカスタムクラスをマージ
    private function mergeControllers($coreClass, $customClass)
    {
        return new class($coreClass, $customClass) extends $coreClass {
            public function __construct($coreClass, $customClass)
            {
                parent::__construct();
                $this->custom = new $customClass();
            }

            public function __call($method, $args)
            {
                if (method_exists($this->custom, $method)) {
                    return $this->custom->$method(...$args);
                }

                return parent::__call($method, $args);
            }
        };
    }

    */
}
