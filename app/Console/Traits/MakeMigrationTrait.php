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

namespace App\Console\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

/**
 * マイグレーションを作成するための追加ロジック。
 * -> MakeFileTrait を use して継承
 *
 * Laravel標準 make:migration に準拠しながら、
 * plugin/custom 用にフォルダを変えるイメージ
 */
trait MakeMigrationTrait
{
    use MakeFileTrait;


    /**
     * マイグレーション固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--create= : The table to be created}',
            '{--table= : The table to migrate}',
        ];
    }

    /**
     * マイグレーションファイルを作成するメイン処理
     *
     * @param  string  $className  クラス名（例：CreateUsersTable）
     * @param  string  $fileType   ファイルタイプ
     * @param  array   $options    オプション配列
     * @param  array   $subDirs    サブディレクトリ配列
     * @param  string  $pluginName プラグイン名
     * @return bool
     */
    protected function makeFile(
        string $className,
        string $fileType,
        array $options,
        array $subDirs,
        string $pluginName
    ): bool {
        // テーブル名を取得
        $tableName = $options['create'] ?? $options['table'] ?? null;
        
        // マイグレーション名を生成
        $migrationName = $className;
        if (empty($migrationName) && $tableName) {
            $prefix = isset($options['create']) ? 'create_' : 'add_';
            $migrationName = $prefix . $tableName . '_table';
        }
        
        // スネークケースに変換してタイムスタンプを追加
        $timestamp = date('Y_m_d_His');
        $snakeCaseName = Str::snake($migrationName);
        $className = "{$timestamp}_{$snakeCaseName}";

        // スタブの取得
        $stub = $this->renderStub($options);

        // プレースホルダーを準備
        $placeholders = [
            'class' => Str::studly($className),
            'table' => $tableName ?? '',
        ];

        // ファイル生成
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'migration',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );

        return true;
    }

    /**
     * マイグレーション用のスタブファイルをレンダリングする
     * 
     * @param array $options オプション
     * @return string スタブファイルの内容
     */
    protected function renderStub(array $options = []): string
    {
        // スタブファイル名を決定
        if (array_key_exists('create', $options)) {
            $stubName = 'migration.create.stub';
        } elseif (array_key_exists('table', $options)) {
            $stubName = 'migration.update.stub';
        } else {
            $stubName = 'migration.stub';
        }

        // スタブファイルのパスを取得
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        // スタブファイルの内容を取得
        return File::get($stubPath);
    }


}
