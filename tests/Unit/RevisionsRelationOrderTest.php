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

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HasRevisions::revisions() returns the newest revision first even when
 * several share the same created_at (#985).
 */
class RevisionsRelationOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_second_revisions_come_back_newest_first(): void
    {
        $page = FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'title' => 'Title',
            'content' => 'v0',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $service = app(RevisionService::class);
        for ($i = 1; $i <= 3; $i++) {
            $page->update(['content' => "v{$i}"]);
            $service->record($page->fresh());
        }

        // Pin every revision to one timestamp, so only the tie-breaker decides.
        FrontPageRevision::query()
            ->where('front_page_id', $page->id)
            ->update(['created_at' => '2026-01-01 00:00:00']);

        $contents = $page->revisions()->get()
            ->map(fn (FrontPageRevision $revision): string => $revision->snapshot['content'])
            ->all();

        $this->assertSame(['v3', 'v2', 'v1'], $contents);
    }
}
