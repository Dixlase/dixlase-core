<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use Illuminate\Support\Facades\Storage;

/**
 * コンテンツファイル管理トレイト
 * ファイルベースのコンテンツ保存に関する共通機能を提供
 * コアとプラグインの両方で使用可能
 */
trait ManagesContentFiles
{
    /**
     * ファイル保存用のディスク
     */
    protected string $disk = 'local';

    /**
     * ファイル保存用のベースパス
     */
    protected string $basePath;

    /**
     * エディタータイプに対応するファイル拡張子
     */
    protected array $extensions = [
        'markdown' => 'md',
        'html' => 'html',
        'blade' => 'blade.php',
    ];

    /**
     * デフォルト言語（ファイル名に言語コードを付けない言語）
     */
    protected string $defaultLocale = 'en';

    /**
     * ベースパスを設定
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * ディスクを設定
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * デフォルト言語を設定
     */
    public function setDefaultLocale(string $locale): self
    {
        $this->defaultLocale = $locale;

        return $this;
    }

    /**
     * ファイルからコンテンツを読み込む
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @return string|null ファイルの内容、存在しない場合はnull
     */
    public function loadFromFile(string $slug, string $locale, string $editorType): ?string
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->get($filePath);
        }

        return null;
    }

    /**
     * コンテンツをファイルに保存する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @param  string  $content  コンテンツ
     * @return bool 保存成功時はtrue
     */
    public function saveToFile(string $slug, string $locale, string $editorType, string $content): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        // ディレクトリが存在しない場合は作成
        $directory = dirname($filePath);
        if (! Storage::disk($this->disk)->exists($directory)) {
            Storage::disk($this->disk)->makeDirectory($directory);
        }

        return Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * ファイルを削除する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @return bool 削除成功時はtrue
     */
    public function deleteFile(string $slug, string $locale, string $editorType): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }

    /**
     * 全言語のファイルを削除する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $editorType  エディタータイプ
     * @param  array  $locales  言語コードの配列
     * @return bool すべて削除成功時はtrue
     */
    public function deleteAllFiles(string $slug, string $editorType, array $locales): bool
    {
        $success = true;
        foreach ($locales as $locale) {
            if (! $this->deleteFile($slug, $locale, $editorType)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * スラッグ変更時にディレクトリをリネームする
     *
     * @param  string  $oldSlug  旧スラッグ
     * @param  string  $newSlug  新スラッグ
     * @param  string  $editorType  エディタータイプ（未使用、互換性のため残す）
     * @param  array  $locales  言語コードの配列（未使用、互換性のため残す）
     * @return bool リネーム成功時はtrue
     */
    public function renameFiles(string $oldSlug, string $newSlug, string $editorType, array $locales): bool
    {
        $oldDir = "{$this->basePath}/{$oldSlug}";
        $newDir = "{$this->basePath}/{$newSlug}";

        // ディレクトリが存在する場合はリネーム
        if (Storage::disk($this->disk)->exists($oldDir)) {
            return Storage::disk($this->disk)->move($oldDir, $newDir);
        }

        return true;
    }

    /**
     * スラッグのディレクトリを削除する
     *
     * @param  string  $slug  スラッグ
     * @return bool 削除成功時はtrue
     */
    public function deleteDirectory(string $slug): bool
    {
        $directory = "{$this->basePath}/{$slug}";

        if (Storage::disk($this->disk)->exists($directory)) {
            return Storage::disk($this->disk)->deleteDirectory($directory);
        }

        return true;
    }

    /**
     * ファイルパスを取得する
     * 構造: {basePath}/{slug}/content.{locale}.{extension}
     * デフォルト言語はファイル名に言語コードを付けない
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @return string ファイルパス
     */
    public function getFilePath(string $slug, string $locale, string $editorType): string
    {
        $extension = $this->extensions[$editorType] ?? 'txt';

        // デフォルト言語はファイル名に言語コードを付けない
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/{$slug}/content.{$extension}";
        }

        return "{$this->basePath}/{$slug}/content.{$locale}.{$extension}";
    }

    /**
     * ファイルの絶対パスを取得する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @return string 絶対ファイルパス
     */
    public function getAbsoluteFilePath(string $slug, string $locale, string $editorType): string
    {
        $relativePath = $this->getFilePath($slug, $locale, $editorType);

        return Storage::disk($this->disk)->path($relativePath);
    }

    /**
     * ファイルが存在するか確認する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @return bool ファイルが存在する場合はtrue
     */
    public function fileExists(string $slug, string $locale, string $editorType): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        return Storage::disk($this->disk)->exists($filePath);
    }

    /**
     * 拡張子を追加または上書き
     *
     * @param  string  $editorType  エディタータイプ
     * @param  string  $extension  拡張子
     */
    public function addExtension(string $editorType, string $extension): self
    {
        $this->extensions[$editorType] = $extension;

        return $this;
    }

    /**
     * ベースパスを取得
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * ディスク名を取得
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * デフォルト言語を取得
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }
}
