<?php

/**
 * This file is part of Dixlase Legal.
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

namespace Plugins\DixlaseLegal\Database\Factories;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\DixlaseLegal\App\Models\DixlaseLegalPage;

/**
 * 法務ページコンテンツのファクトリ
 *
 * @extends Factory<DixlaseLegalPage>
 */
class DixlaseLegalPageFactory extends Factory
{
    /** @var class-string<DixlaseLegalPage> */
    protected $model = DixlaseLegalPage::class;

    /**
     * デフォルト定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slugs = ['privacy-policy', 'terms-of-service', 'site-policy', 'cookie-policy', 'tokushoho'];

        return [
            'slug' => fake()->randomElement($slugs),
            'lang' => fake()->randomElement(['ja', 'en']),
            'title' => fake()->sentence(3),
            'content_html' => '<p>' . fake()->paragraphs(3, true) . '</p>',
            'content_markdown' => null,
            'editor_type' => ContentEditorType::HTML,
            'status' => ContentStatus::DRAFT,
            'published_at' => null,
        ];
    }

    /**
     * 公開済み状態
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * 下書き状態
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * 日付指定状態
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::SCHEDULED,
            'published_at' => now()->addDays(7),
        ]);
    }

    /**
     * 日本語コンテンツ
     */
    public function japanese(): static
    {
        return $this->state(fn (array $attributes) => [
            'lang' => 'ja',
        ]);
    }

    /**
     * 英語コンテンツ
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'lang' => 'en',
        ]);
    }

    /**
     * Markdownエディタ
     */
    public function markdown(): static
    {
        return $this->state(fn (array $attributes) => [
            'editor_type' => ContentEditorType::MARKDOWN,
            'content_markdown' => "# " . fake()->sentence() . "\n\n" . fake()->paragraphs(3, true),
            'content_html' => null,
        ]);
    }
}
