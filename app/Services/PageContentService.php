<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Parsedown;

/**
 * ページコンテンツ管理サービス
 *
 * ページコンテンツやフロントページのデザインなど、
 * 編集可能なコンテンツの保存・読み込み・変換を管理します。
 */
class PageContentService
{
    /**
     * ファイル保存のベースディレクトリ
     */
    protected string $baseDirectory = 'pages';

    /**
     * ベースディレクトリを設定
     */
    public function setBaseDirectory(string $directory): self
    {
        $this->baseDirectory = $directory;

        return $this;
    }

    /**
     * コンテンツを保存
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  string  $content  コンテンツ
     * @param  ContentStorageType  $storageType  保存方法
     * @param  ContentEditorType  $editorType  エディタータイプ
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public function saveContent(
        string $identifier,
        string $content,
        ContentStorageType $storageType,
        ContentEditorType $editorType
    ): array {
        try {
            if ($storageType === ContentStorageType::FILE) {
                return $this->saveToFile($identifier, $content, $editorType);
            }

            // DATABASE の場合は呼び出し元で保存
            return [
                'success' => true,
                'path' => null,
                'message' => 'Content will be saved to database',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to save content', [
                'identifier' => $identifier,
                'storage_type' => $storageType->value,
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'path' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * コンテンツを読み込み
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  ContentStorageType  $storageType  保存方法
     * @param  ContentEditorType  $editorType  エディタータイプ
     * @param  string|null  $dbContent  DB保存の場合のコンテンツ
     */
    public function loadContent(
        string $identifier,
        ContentStorageType $storageType,
        ContentEditorType $editorType,
        ?string $dbContent = null
    ): ?string {
        try {
            if ($storageType === ContentStorageType::FILE) {
                return $this->loadFromFile($identifier, $editorType);
            }

            // DATABASE の場合
            return $dbContent;
        } catch (\Exception $e) {
            Log::error('Failed to load content', [
                'identifier' => $identifier,
                'storage_type' => $storageType->value,
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * コンテンツをレンダリング用に変換
     *
     * @param  string  $content  コンテンツ
     * @param  ContentEditorType  $editorType  エディタータイプ
     * @param  ContentStorageType  $storageType  保存方法
     */
    public function renderContent(
        string $content,
        ContentEditorType $editorType,
        ContentStorageType $storageType
    ): string {
        try {
            return match ($editorType) {
                ContentEditorType::MARKDOWN => $this->renderMarkdown($content),
                ContentEditorType::HTML => $content,
                ContentEditorType::BLADE => $storageType === ContentStorageType::FILE
                    ? $content // Bladeファイルは別途レンダリング
                    : $content,
                ContentEditorType::GUI => $this->renderGui($content),
            };
        } catch (\Exception $e) {
            Log::error('Failed to render content', [
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return $content;
        }
    }

    /**
     * ファイルに保存
     */
    protected function saveToFile(
        string $identifier,
        string $content,
        ContentEditorType $editorType
    ): array {
        $filename = $this->generateFilename($identifier, $editorType);
        $path = storage_path("app/{$this->baseDirectory}/{$filename}");

        // ディレクトリが存在しない場合は作成
        $directory = dirname($path);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // ファイルに保存
        File::put($path, $content);

        return [
            'success' => true,
            'path' => $path,
            'message' => "Content saved to file: {$filename}",
        ];
    }

    /**
     * ファイルから読み込み
     */
    protected function loadFromFile(
        string $identifier,
        ContentEditorType $editorType
    ): ?string {
        $filename = $this->generateFilename($identifier, $editorType);
        $path = storage_path("app/{$this->baseDirectory}/{$filename}");

        if (! File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    /**
     * ファイルを削除
     */
    public function deleteFile(string $identifier, ContentEditorType $editorType): bool
    {
        try {
            $filename = $this->generateFilename($identifier, $editorType);
            $path = storage_path("app/{$this->baseDirectory}/{$filename}");

            if (File::exists($path)) {
                File::delete($path);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete content file', [
                'identifier' => $identifier,
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ファイル名を生成
     */
    protected function generateFilename(string $identifier, ContentEditorType $editorType): string
    {
        $slug = Str::slug($identifier);
        $extension = $editorType->fileExtension();

        return "{$slug}.{$extension}";
    }

    /**
     * ファイルパスを取得
     */
    public function getFilePath(string $identifier, ContentEditorType $editorType): string
    {
        $filename = $this->generateFilename($identifier, $editorType);

        return storage_path("app/{$this->baseDirectory}/{$filename}");
    }

    /**
     * ファイルが存在するか確認
     */
    public function fileExists(string $identifier, ContentEditorType $editorType): bool
    {
        $path = $this->getFilePath($identifier, $editorType);

        return File::exists($path);
    }

    /**
     * Markdownをレンダリング
     */
    protected function renderMarkdown(string $content): string
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false); // HTMLタグを許可

        return $parsedown->text($content);
    }

    /**
     * GUIコンテンツをレンダリング（将来実装）
     */
    protected function renderGui(string $content): string
    {
        // TODO: GUIエディタのJSON形式をHTMLに変換
        // 現時点ではそのまま返す
        return $content;
    }

    /**
     * Bladeビューとしてレンダリング
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  array  $data  ビューに渡すデータ
     */
    public function renderBladeView(string $identifier, array $data = []): string
    {
        try {
            $viewPath = "{$this->baseDirectory}.".str_replace('/', '.', Str::slug($identifier));

            if (view()->exists($viewPath)) {
                return view($viewPath, $data)->render();
            }

            return '';
        } catch (\Exception $e) {
            Log::error('Failed to render Blade view', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * 保存方法を変更（マイグレーション）
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  ContentStorageType  $fromStorage  元の保存方法
     * @param  ContentStorageType  $toStorage  新しい保存方法
     * @param  ContentEditorType  $editorType  エディタータイプ
     * @param  string|null  $dbContent  DB保存の場合のコンテンツ
     * @return array ['success' => bool, 'content' => string|null, 'message' => string]
     */
    public function migrateStorage(
        string $identifier,
        ContentStorageType $fromStorage,
        ContentStorageType $toStorage,
        ContentEditorType $editorType,
        ?string $dbContent = null
    ): array {
        try {
            // コンテンツを読み込み
            $content = $this->loadContent($identifier, $fromStorage, $editorType, $dbContent);

            if ($content === null) {
                return [
                    'success' => false,
                    'content' => null,
                    'message' => 'Content not found',
                ];
            }

            // 新しい保存方法で保存
            $result = $this->saveContent($identifier, $content, $toStorage, $editorType);

            // 元のファイルを削除（FILE → DATABASE の場合）
            if ($fromStorage === ContentStorageType::FILE && $toStorage === ContentStorageType::DATABASE) {
                $this->deleteFile($identifier, $editorType);
            }

            return [
                'success' => $result['success'],
                'content' => $content,
                'message' => $result['message'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to migrate storage', [
                'identifier' => $identifier,
                'from_storage' => $fromStorage->value,
                'to_storage' => $toStorage->value,
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'content' => null,
                'message' => $e->getMessage(),
            ];
        }
    }
}
