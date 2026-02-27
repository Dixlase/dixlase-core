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

namespace Database\Factories;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Models\FrontPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * フロントページファクトリ
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FrontPage>
 */
class FrontPageFactory extends Factory
{
    protected $model = FrontPage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_type' => 'main_content',
            'lang' => 'en',
            'title' => null,
            'content' => '<h1>Welcome</h1><p>This is the front page.</p>',
            'storage_type' => ContentStorageType::DATABASE->value,
            'editor_type' => ContentEditorType::HTML->value,
            'status' => ContentStatus::PUBLISHED->value,
        ];
    }

    /**
     * Markdown エディタで作成
     */
    public function markdown(): static
    {
        return $this->state(fn () => [
            'editor_type' => ContentEditorType::MARKDOWN->value,
            'content' => "# Welcome\n\nThis is the front page.",
        ]);
    }

    /**
     * ファイル保存で作成
     */
    public function fileStorage(): static
    {
        return $this->state(fn () => [
            'storage_type' => ContentStorageType::FILE->value,
        ]);
    }

    /**
     * 下書きで作成
     */
    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ContentStatus::DRAFT->value,
        ]);
    }

    /**
     * 日本語で作成
     */
    public function japanese(): static
    {
        return $this->state(fn () => [
            'lang' => 'ja',
            'content' => '<h1>ようこそ</h1><p>フロントページです。</p>',
        ]);
    }
}
