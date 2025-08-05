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

/**
 * ポリシーを作るための追加ロジック。
 * -> MakeFileTrait を use し、model指定あり/なしを含む処理をまとめる例
 */
trait MakePolicyTrait
{
    use MakeFileTrait;

    /**
     * ポリシーを作成するメイン処理
     *
     * @param  string       $className   ポリシークラス名 (e.g. "UserPolicy")
     * @param  array        $subDirs
     * @param  bool         $force
     * @param  string|null  $modelOption --model= で指定されたモデルFQCN or 相対パス
     * @return void
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        ?string $modelOption
    ): void {
        // 1) どの stub を使うか (modelあり → policy.stub, なし → policy.plain.stub)
        $stubFile = $modelOption ? 'policy.stub' : 'policy.plain.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) ポリシー固有の追加プレースホルダ(あとでまとめる)
        //    modelFQCN, userFQCNなどは後で replace する場合はここでも良いが
        //    ここでは一旦空にしておき、makeFiler呼出し直前にマージする設計もできる

        // => ここではとりあえず空でOK
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders, 'policies');
    }

    /**
     * Get additional options specific to policy generation
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--model=} ' . __('The model that the policy applies to'),
            '{--guard=} ' . __('The guard that the policy relies on')
        ];
    }

    /**
     * Get the model class name for the policy.
     *
     * @param  string  $modelOption
     * @param  string  $pluginName
     * @return string
     */
    protected function qualifyModel(string $modelOption, string $pluginName): string
    {
        if (Str::startsWith($modelOption, '\\')) {
            $modelOption = Str::replaceFirst('\\', '', $modelOption);
        }
        
        if (Str::contains($modelOption, '\\')) {
            return $modelOption;
        }
        
        return "Plugins\\{$pluginName}\\App\\Models\\{$modelOption}";
    }

    /**
     * Get the user model class name.
     *
     * @return string
     */
    protected function qualifyUserModel(): string
    {
        return config('auth.providers.users.model', 'App\\Models\\User');
    }
}
