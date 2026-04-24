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
use App\Models\BaseSetting;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * リビジョン保護フラグ (is_protected) の自動削除除外と countProtected のユニットテスト
 */
class RevisionProtectionTest extends TestCase
{
    use RefreshDatabase;

    private RevisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RevisionService::class);
    }

    private function makeFrontPage(string $content = 'v0'): FrontPage
    {
        return FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'title' => 'Title',
            'content' => $content,
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);
    }

    public function test_prune_keeps_protected_revisions_even_beyond_retention(): void
    {
        BaseSetting::setValue(RevisionService::SETTING_KEY_RETENTION, 3);

        $page = $this->makeFrontPage('v0');

        // v1..v3 を保護なしで作成
        for ($i = 1; $i <= 3; $i++) {
            $page->update(['content' => "v{$i}"]);
            $this->service->record($page->fresh());
        }

        // v1 を保護
        $v1 = FrontPageRevision::query()
            ->where('front_page_id', $page->id)
            ->orderBy('created_at')
            ->first();
        $v1->update(['is_protected' => true]);

        // さらに v4, v5 を追加（保護なし）
        for ($i = 4; $i <= 5; $i++) {
            $page->update(['content' => "v{$i}"]);
            $this->service->record($page->fresh());
        }

        $remaining = FrontPageRevision::where('front_page_id', $page->id)->get();

        // 保護 v1 は残り、非保護は新しい順に 2 件（retention=3 - 保護1 = 2）
        $this->assertTrue($remaining->contains('id', $v1->id), '保護された v1 は残存すべき');
        $this->assertSame(3, $remaining->count(), '合計は retention 件数と一致');
    }

    public function test_prune_never_deletes_protected_revisions(): void
    {
        BaseSetting::setValue(RevisionService::SETTING_KEY_RETENTION, 2);

        $page = $this->makeFrontPage('v0');

        // まず 2 件作って両方保護
        $protectedIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $page->update(['content' => "v{$i}"]);
            $rev = $this->service->record($page->fresh());
            $rev->update(['is_protected' => true]);
            $protectedIds[] = $rev->id;
        }

        // 3 件目（非保護）を追加 → overflow=1、非保護が新規作成分のみなので
        // 新規作成分が即座に削除される（保護された既存 2 件は残る）
        $page->update(['content' => 'v3']);
        $this->service->record($page->fresh());

        $remaining = FrontPageRevision::where('front_page_id', $page->id)->pluck('id')->all();

        $this->assertCount(2, $remaining, '保護された 2 件のみが残る');
        foreach ($protectedIds as $id) {
            $this->assertContains($id, $remaining, '保護されたリビジョンは削除されない');
        }
    }

    public function test_prune_exceeds_retention_when_all_revisions_are_protected(): void
    {
        BaseSetting::setValue(RevisionService::SETTING_KEY_RETENTION, 2);

        $page = $this->makeFrontPage('v0');

        // モデル直接作成で 3 件の保護リビジョンを用意（retention を超える状態）
        for ($i = 1; $i <= 3; $i++) {
            FrontPageRevision::create([
                'front_page_id' => $page->id,
                'snapshot' => ['content' => "v{$i}"],
                'type' => FrontPageRevision::TYPE_MANUAL,
                'is_protected' => true,
                'created_at' => now()->addSeconds($i),
            ]);
        }

        $this->assertSame(3, FrontPageRevision::where('front_page_id', $page->id)->count());

        // このあと非保護を追加してトリガーする
        $page->update(['content' => 'v4']);
        $this->service->record($page->fresh());

        $total = FrontPageRevision::where('front_page_id', $page->id)->count();
        // 4 件のうち、overflow=2。非保護は新規分のみ1件。この1件が削除される → 3件残る
        $this->assertSame(3, $total, '保護済み 3 件は残り、新規非保護は overflow で削除される');
    }

    public function test_count_protected_returns_only_protected(): void
    {
        $page = $this->makeFrontPage('v0');

        $page->update(['content' => 'v1']);
        $rev1 = $this->service->record($page->fresh());
        $page->update(['content' => 'v2']);
        $rev2 = $this->service->record($page->fresh());
        $page->update(['content' => 'v3']);
        $this->service->record($page->fresh());

        $rev1->update(['is_protected' => true]);
        $rev2->update(['is_protected' => true]);

        $this->assertSame(2, $this->service->countProtected($page));
    }
}
