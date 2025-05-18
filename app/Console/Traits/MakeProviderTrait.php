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

use Illuminate\Support\Facades\Artisan;

/**
 * プロバイダー作成用トレイト
 * MakeModelTrait と同じパターンに従う
 */
trait MakeProviderTrait
{
    use MakeFileTrait;

    /**
     * プロバイダー固有のオプション定義
     */
    protected $providerSpecificOptions = [
        '{--plugin : プラグイン用のテンプレートを使用します}',
        '{--scope= : プロバイダーのスコープ（例: plain, auth, event）}',
    ];

    protected $providerOptions;

    public static function bootMakeProviderTrait()
    {
        $providerOptions = array_merge(
            ['{className : サービスプロバイダの名前（例: MyPluginServiceProvider）}'],
            static::$commonOptions,
            static::$providerSpecificOptions
        );
    }

    /**
     * 新しいサービスプロバイダーを作成します。
     *
     * @param  string  $className  クラス名
     * @param  string  $fileType   ファイルタイプ
     * @param  array   $options    オプション配列
     * @param  array   $subDirs    サブディレクトリ配列
     * @param  string  $pluginName プラグイン名
     * @param  array   $licenseInfo ライセンス情報
     * @return void
     */
    protected function makeFile(
        string $className,
        string $fileType,
        array $options,
        array $subDirs,
        string $pluginName = '',
        array $licenseInfo = []
    ): void {
        // 1) スタブファイルを決定
        $stubFile = $this->renderStub($options);

        // 2) プレースホルダーを準備
        $extraPlaceholders = $this->prepareProviderPlaceholders($licenseInfo);

        // 3) ファイルを生成
        $this->makeFiler(
            $className,
            $fileType,
            'providers',
            $options,
            $subDirs,
            $stubFile,
            $pluginName,
            $extraPlaceholders,
            $licenseInfo
        );
    }

    /**
     * オプションに基づいて適切なスタブファイルを取得します。
     *
     * @param  array  $options オプション配列
     * @return string スタブファイル名
     */
    protected function renderStub(array $options): string
    {
        return $options['plugin'] ?? false 
            ? 'provider.plugin.stub' 
            : 'provider.stub';
    }
    /**
     * プロバイダー用のプレースホルダーを準備します。
     *
     * @param  array  $licenseInfo ライセンス情報
     * @return array プレースホルダー配列
     */
    protected function prepareProviderPlaceholders(array $licenseInfo = []): array
    {
        return [
            '{{ license }}' => isset($this->fileGenerator) 
                ? $this->fileGenerator->getLicenseForPhp($licenseInfo) 
                : ''
        ];
    }

    /**
     * プロバイダー固有の追加オプションを取得
     *
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            'plugin' => true, // プラグインプロバイダーの場合は常に true
            //'scope' => $this->option('scope', 'plain'),
        ];
    }

    /**
     * プロバイダーのディレクトリパスを取得します。
     * 
     * @param  array  $subDirs サブディレクトリ配列
     * @return string ディレクトリパス
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getProviderDirectory($subDirs);
    }

    /**
     * プロバイダーの名前空間を取得します。
     * 
     * @param  array  $subDirs サブディレクトリ配列
     * @return string 名前空間
     */
    protected function getNamespace(array $subDirs): string
    {
        return $this->getProviderNamespace($subDirs);
    }

    /**
     * プロバイダーのディレクトリパスを取得します（このトレイトを使用するクラスで実装が必要）
     * 
     * @param  array  $subDirs サブディレクトリ配列
     * @return string ディレクトリパス
     */
    abstract protected function getProviderDirectory(array $subDirs): string;
    
    /**
     * プロバイダーの名前空間を取得します（このトレイトを使用するクラスで実装が必要）
     * 
     * @param  array  $subDirs サブディレクトリ配列
     * @return string 名前空間
     */
    abstract protected function getProviderNamespace(array $subDirs): string;
}
