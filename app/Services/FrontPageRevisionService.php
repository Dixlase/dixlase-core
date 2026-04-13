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

use App\Models\BaseSetting;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use Illuminate\Support\Facades\DB;

/**
 * フロントページのリビジョン作成・復元・クリーンアップを担うサービス
 *
 * 将来 Phase 2 で `RevisionService` として汎用化する想定。
 */
class FrontPageRevisionService
{
    /** 設定キー: リビジョン保持件数（0 でリビジョン無効、上限 500） */
    public const SETTING_KEY_RETENTION = 'content.revision.retention_count';

    public const DEFAULT_RETENTION = 50;

    public const MAX_RETENTION = 500;

    /**
     * スナップショットに含めるカラム（比較・復元対象）
     *
     * @var list<string>
     */
    private const SNAPSHOT_FIELDS = [
        'page_type',
        'lang',
        'title',
        'content',
        'custom_js',
        'custom_css',
        'storage_type',
        'editor_type',
        'status',
    ];

    /**
     * リビジョンを作成する。
     *
     * 直前リビジョンと同一内容なら作成をスキップする（auto/manual 共通）。
     * 作成後、保持件数を超えた古いリビジョンを削除する。
     *
     * @return FrontPageRevision|null 作成されたリビジョン（スキップ時は null）
     */
    public function record(FrontPage $frontPage, string $type = FrontPageRevision::TYPE_AUTO, ?int $userId = null, ?string $note = null): ?FrontPageRevision
    {
        $retention = $this->getRetentionCount();
        if ($retention === 0) {
            return null;
        }

        $snapshot = $this->buildSnapshot($frontPage);

        if ($this->matchesLatest($frontPage, $snapshot)) {
            return null;
        }

        return DB::transaction(function () use ($frontPage, $snapshot, $type, $userId, $note, $retention) {
            $revision = FrontPageRevision::create([
                'front_page_id' => $frontPage->id,
                'snapshot' => $snapshot,
                'type' => $type,
                'note' => $note,
                'created_by' => $userId,
            ]);

            $this->pruneOldRevisions($frontPage, $retention);

            return $revision;
        });
    }

    /**
     * リビジョンから復元する。
     *
     * 復元前の現状が直前リビジョンと差分がある場合のみ restore_backup を作成する。
     */
    public function restore(FrontPageRevision $revision, ?int $userId = null): FrontPage
    {
        $frontPage = $revision->frontPage;

        return DB::transaction(function () use ($frontPage, $revision, $userId) {
            $this->record($frontPage, FrontPageRevision::TYPE_RESTORE_BACKUP, $userId);

            $snapshot = $revision->snapshot;
            $updateData = array_intersect_key($snapshot, array_flip(self::SNAPSHOT_FIELDS));
            $frontPage->update($updateData);

            return $frontPage->fresh();
        });
    }

    /**
     * 現在の FrontPage からスナップショット配列を生成する。
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(FrontPage $frontPage): array
    {
        $attributes = $frontPage->getAttributes();

        // 全フィールドを必ず含める（未設定キーは null で埋める）ことで、
        // in-memory モデルと DB 再ロード後でキーの有無が変わらないよう正規化する。
        $snapshot = [];
        foreach (self::SNAPSHOT_FIELDS as $field) {
            $snapshot[$field] = $attributes[$field] ?? null;
        }

        return $snapshot;
    }

    /**
     * 直前リビジョンのスナップショットと現在のスナップショットが同一かを判定する。
     *
     * JSON を正規化（キーソート）したハッシュで比較する。
     *
     * @param  array<string, mixed>  $snapshot
     */
    private function matchesLatest(FrontPage $frontPage, array $snapshot): bool
    {
        $latest = FrontPageRevision::query()
            ->where('front_page_id', $frontPage->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return false;
        }

        return $this->hash($latest->snapshot ?? []) === $this->hash($snapshot);
    }

    /**
     * スナップショット配列の正規化ハッシュを返す。
     *
     * @param  array<string, mixed>  $snapshot
     */
    private function hash(array $snapshot): string
    {
        // enum インスタンスや日時など、DB 由来の JSON と型が一致しない値を
        // プリミティブに正規化するため、一度 JSON を往復させてから比較する。
        $normalized = json_decode(
            (string) json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            true
        ) ?? [];
        ksort($normalized);

        return hash('sha256', (string) json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * 保持件数を超えた古いリビジョンを削除する。
     */
    private function pruneOldRevisions(FrontPage $frontPage, int $retention): void
    {
        $ids = FrontPageRevision::query()
            ->where('front_page_id', $frontPage->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->skip($retention)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            FrontPageRevision::query()->whereIn('id', $ids)->delete();
        }
    }

    /**
     * 保持件数設定値を取得する（0〜MAX_RETENTION の範囲に丸める）。
     */
    public function getRetentionCount(): int
    {
        $value = (int) BaseSetting::getValue(self::SETTING_KEY_RETENTION, self::DEFAULT_RETENTION);

        return max(0, min(self::MAX_RETENTION, $value));
    }
}
