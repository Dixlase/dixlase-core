<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Models\FrontPage;
use Illuminate\Database\Seeder;

/**
 * Seed a minimal welcome front page so the freshly-installed site is not
 * a blank canvas.
 *
 * One row per shipped locale (en + ja) for the primary site. Uses
 * firstOrCreate to preserve operator edits — once the operator has
 * customized or deleted the welcome page for a locale, re-seeding will
 * not overwrite. Deliberately minimal (a single H1 + one paragraph) so
 * the operator can wipe it in seconds and start fresh without clutter.
 *
 * Roadmap for richer default-content variants (onboarding checklist,
 * capability showcase, editor-level placeholder, install-time opt-in)
 * lives in .backlog/front-page-default-content-roadmap.md.
 */
class FrontPagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $primarySiteId = 1;

        $pages = [
            'en' => [
                'title' => 'Welcome',
                'content' => <<<'HTML'
<h1>Welcome to your Dixlase site</h1>
<p>This is your front page. Edit or replace this content from the admin panel at <a href="/admin/front">/admin/front</a>. You can use HTML or Markdown depending on the editor mode.</p>
HTML,
            ],
            'ja' => [
                'title' => 'ようこそ',
                'content' => <<<'HTML'
<h1>Dixlase サイトへようこそ</h1>
<p>これはフロントページの初期コンテンツです。管理画面 <a href="/admin/front">/admin/front</a> から編集または置き換えできます。エディタモードに応じて HTML / Markdown を使い分けられます。</p>
HTML,
            ],
        ];

        foreach ($pages as $lang => $page) {
            FrontPage::firstOrCreate(
                [
                    'site_id' => $primarySiteId,
                    'page_type' => 'main_content',
                    'lang' => $lang,
                ],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'editor_type' => ContentEditorType::HTML,
                    'storage_type' => ContentStorageType::DATABASE,
                    'status' => ContentStatus::PUBLISHED,
                ]
            );
        }
    }
}
