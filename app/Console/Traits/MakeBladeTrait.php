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

namespace App\Console\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

/**
 * Bladeファイルを作成するためのTrait。
 * -> いわゆる「クラス + namespace」が存在しないため、MakeFileTraitは使わず、
 *    シンプルに「フォルダ+ファイル」を生成するだけに特化する。
 */
trait MakeBladeTrait
{
    /**
     * Bladeファイルを作成するメイン処理
     *
     * @param  string  $viewName   例: "admin/dashboard", "home/index" など
     * @param  bool    $force
     * @return void
     */
    protected function makeBlade(string $viewName, bool $force): void
    {
        // 1) もし "admin/dashboard" のように入力されたら
        //    → admin/dashboard.blade.php に変換
        if (! Str::endsWith($viewName, '.blade.php')) {
            $viewName .= '.blade.php';
        }

        // 2) basePath (コア用、カスタム用、プラグイン用) はサブクラスで実装
        $targetPath = $this->getBladeBasePath() . '/' . $viewName;

        // ディレクトリ生成
        File::ensureDirectoryExists(dirname($targetPath), 0755, true);

        // 3) --force でない場合、既存チェック
        if (! $force && File::exists($targetPath)) {
            $this->error("Blade file [{$targetPath}] already exists. Use --force to overwrite.");
            return;
        } else {
            // 上書きする場合、一旦削除
            if (File::exists($targetPath)) {
                File::delete($targetPath);
            }
        }

        // 4) stubファイルを取得
        $stubPath = $this->getBladeStubPath();
        if (! File::exists($stubPath)) {
            $this->error("Stub file [{$stubPath}] not found. Please create it or configure path.");
            return;
        }

        $stubContent = File::get($stubPath);

        // 5) 必要なライセンスコメントをBlade形式に変換して埋め込むなど
        $licenseBladeComment = $this->getLicenseBladeComment();
        // stub内に {{ license }} があれば置換
        $finalContent = str_replace('{{ license }}', $licenseBladeComment, $stubContent);

        // 6) 出力
        File::put($targetPath, $finalContent);

        $this->info("Blade file created: {$targetPath}");
    }

    /**
     * Bladeファイルの保存先パス（ベースパス）
     * ex) "resources/views", "custom/views", "plugins/{Plugin}/resources/views" etc
     */
    abstract protected function getBladeBasePath(): string;

    /**
     * Bladeのstubファイルのパス
     * 例: stubs/blade.stub
     */
    protected function getBladeStubPath(): string
    {
        // 好みでcustom stubsを検索してもよい
        return base_path('stubs/blade.stub');
    }

    /**
     * ライセンスコメントをBlade形式に整形 (任意)
     */
    protected function getLicenseBladeComment(): string
    {
        // ここではダミーとして空文字を返す例
        // 実装例は先の `getLicenseBladeComment()` と同様。
        return '';
    }
}
