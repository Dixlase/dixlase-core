<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Services;

use App\Enums\ContentStorageType;
use App\Models\FrontPage;

/**
 * @api プラグイン/テーマから直接DIで使用可能な安定APIです
 *
 * フロントページコンテンツサービス
 * コアのContentFileServiceを継承し、フロントページ固有の機能を追加
 */
class FrontPageContentService extends ContentFileService
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // storage/app/private/front/
        parent::__construct('front', 'local', 'en');
    }

    /**
     * ファイルパスを取得する（フロントページ用にオーバーライド）
     * 構造: {basePath}/content.{locale}.{extension}
     * デフォルト言語はファイル名に言語コードを付けない
     *
     * @param  string  $slug  スラッグ（フロントページでは使用しない）
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @return string ファイルパス
     */
    public function getFilePath(string $slug, string $locale, string $editorType): string
    {
        $extension = $this->extensions[$editorType] ?? 'txt';

        // デフォルト言語はファイル名に言語コードを付けない
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/content.{$extension}";
        }

        return "{$this->basePath}/content.{$locale}.{$extension}";
    }

    /**
     * フロントページのコンテンツを取得する（DB or ファイル）
     *
     * @param  FrontPage  $frontPage  フロントページモデル
     * @param  string|null  $locale  言語コード（nullの場合は現在の言語）
     * @return string|null コンテンツ
     */
    public function getContent(FrontPage $frontPage, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($frontPage->storage_type === ContentStorageType::FILE) {
            return $this->loadFromFile($frontPage->page_type, $locale, $frontPage->editor_type->slug());
        }

        return $frontPage->content ?? null;
    }

    /**
     * フロントページのコンテンツを保存する（DB or ファイル）
     *
     * @param  FrontPage  $frontPage  フロントページモデル
     * @param  string  $content  コンテンツ
     * @param  string|null  $locale  言語コード（nullの場合は現在の言語）
     * @return bool 保存成功時はtrue
     */
    public function saveContent(FrontPage $frontPage, string $content, ?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();

        if ($frontPage->storage_type === ContentStorageType::FILE) {
            return $this->saveToFile($frontPage->page_type, $locale, $frontPage->editor_type->slug(), $content);
        }

        // データベースに保存（コントローラーで行う）
        return true;
    }

    /**
     * JS ファイルパスを取得する
     *
     * @param  string  $slug  スラッグ（フロントページでは使用しない）
     * @param  string  $locale  言語コード
     * @return string ファイルパス
     */
    public function getJsFilePath(string $slug, string $locale): string
    {
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/script.js";
        }

        return "{$this->basePath}/script.{$locale}.js";
    }

    /**
     * CSS ファイルパスを取得する
     *
     * @param  string  $slug  スラッグ（フロントページでは使用しない）
     * @param  string  $locale  言語コード
     * @return string ファイルパス
     */
    public function getCssFilePath(string $slug, string $locale): string
    {
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/style.css";
        }

        return "{$this->basePath}/style.{$locale}.css";
    }

    /**
     * JS コンテンツを取得する（DB or ファイル）
     *
     * @param  FrontPage  $frontPage  フロントページモデル
     * @param  string|null  $locale  言語コード（nullの場合は現在の言語）
     * @return string|null コンテンツ
     */
    public function getJsContent(FrontPage $frontPage, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($frontPage->storage_type === ContentStorageType::FILE) {
            $filePath = $this->getJsFilePath($frontPage->page_type, $locale);

            if (\Illuminate\Support\Facades\Storage::disk($this->disk)->exists($filePath)) {
                return \Illuminate\Support\Facades\Storage::disk($this->disk)->get($filePath);
            }
        }

        return $frontPage->custom_js ?? null;
    }

    /**
     * CSS コンテンツを取得する（DB or ファイル）
     *
     * @param  FrontPage  $frontPage  フロントページモデル
     * @param  string|null  $locale  言語コード（nullの場合は現在の言語）
     * @return string|null コンテンツ
     */
    public function getCssContent(FrontPage $frontPage, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($frontPage->storage_type === ContentStorageType::FILE) {
            $filePath = $this->getCssFilePath($frontPage->page_type, $locale);

            if (\Illuminate\Support\Facades\Storage::disk($this->disk)->exists($filePath)) {
                return \Illuminate\Support\Facades\Storage::disk($this->disk)->get($filePath);
            }
        }

        return $frontPage->custom_css ?? null;
    }

    /**
     * JS コンテンツをファイルに保存する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $content  コンテンツ
     * @return bool 保存成功時はtrue
     */
    public function saveJsToFile(string $slug, string $locale, string $content): bool
    {
        $filePath = $this->getJsFilePath($slug, $locale);

        return \Illuminate\Support\Facades\Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * CSS コンテンツをファイルに保存する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $content  コンテンツ
     * @return bool 保存成功時はtrue
     */
    public function saveCssToFile(string $slug, string $locale, string $content): bool
    {
        $filePath = $this->getCssFilePath($slug, $locale);

        return \Illuminate\Support\Facades\Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * JS ファイルを削除する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return bool 削除成功時はtrue
     */
    public function deleteJsFile(string $slug, string $locale): bool
    {
        $filePath = $this->getJsFilePath($slug, $locale);

        if (\Illuminate\Support\Facades\Storage::disk($this->disk)->exists($filePath)) {
            return \Illuminate\Support\Facades\Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }

    /**
     * CSS ファイルを削除する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return bool 削除成功時はtrue
     */
    public function deleteCssFile(string $slug, string $locale): bool
    {
        $filePath = $this->getCssFilePath($slug, $locale);

        if (\Illuminate\Support\Facades\Storage::disk($this->disk)->exists($filePath)) {
            return \Illuminate\Support\Facades\Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }
}
