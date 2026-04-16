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

namespace App\Services;

use App\Contracts\Revisionable;
use App\Models\BaseSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @api 任意の Revisionable コンテンツに対してリビジョンの記録・復元・削除を扱う汎用サービス
 *
 * コア・プラグイン・テーマ問わず、`Revisionable` を実装するモデルを受けて以下を提供する:
 *   - 保存時スナップショット記録（差分がなければスキップ）
 *   - 保持件数超過時の自動削除（保護フラグは除外）
 *   - 復元（必要なら復元前バックアップを作成）
 *   - 正規化ハッシュによる差分判定
 *
 * 使用例:
 * ```php
 * $service = app(RevisionService::class);
 * $service->record($frontPage, type: 'manual', userId: $actor->getActorId());
 * $service->restore($revision, userId: $actor->getActorId());
 * ```
 *
 * 保持件数は全コンテンツタイプ共通の設定キー `content.revision.retention_count` から取得する。
 * プラグイン側で独自のリテンションポリシーを持たせたい場合は、このサービスを継承して
 * `getRetentionCount()` を上書きすること。
 */
class RevisionService
{
    public const TYPE_AUTO = 'auto';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_RESTORE_BACKUP = 'restore_backup';

    /** 設定キー: リビジョン保持件数（0 でリビジョン無効、上限 MAX_RETENTION） */
    public const SETTING_KEY_RETENTION = 'content.revision.retention_count';

    public const DEFAULT_RETENTION = 50;

    public const MAX_RETENTION = 500;

    /**
     * リビジョンを作成する。
     *
     * 直前リビジョンと同一内容の場合は作成をスキップする。
     * 作成後、保持件数を超えた分の非保護リビジョンを古い順に自動削除する。
     *
     * @return Model|null 作成されたリビジョンモデル（スキップ時は null）
     */
    public function record(
        Revisionable $target,
        string $type = self::TYPE_AUTO,
        ?int $userId = null,
        ?string $note = null,
    ): ?Model {
        $retention = $this->getRetentionCount();
        if ($retention === 0) {
            return null;
        }

        $snapshot = $this->buildSnapshot($target);

        if ($this->matchesLatest($target, $snapshot)) {
            return null;
        }

        return DB::transaction(function () use ($target, $snapshot, $type, $userId, $note, $retention) {
            $revisionClass = $target->revisionModel();
            /** @var Model $revision */
            $revision = $revisionClass::query()->create([
                $target->revisionForeignKey() => $target->getKey(),
                'snapshot' => $snapshot,
                'type' => $type,
                'note' => $note,
                'created_by' => $userId,
            ]);

            $this->pruneOldRevisions($target, $retention);

            return $revision;
        });
    }

    /**
     * 指定リビジョンの内容でコンテンツを復元する。
     *
     * 復元前の現状が直前リビジョンと差分がある場合に限り、
     * `TYPE_RESTORE_BACKUP` の自動バックアップを 1 件作成する。
     */
    public function restore(Model $revision, ?int $userId = null): Revisionable
    {
        $target = $this->loadTarget($revision);

        return DB::transaction(function () use ($target, $revision, $userId) {
            $this->record($target, self::TYPE_RESTORE_BACKUP, $userId);

            /** @var array<string, mixed> $snapshot */
            $snapshot = $revision->snapshot ?? [];
            $updateData = array_intersect_key($snapshot, array_flip($target->revisionableFields()));

            /** @var Model $targetModel */
            $targetModel = $target;
            $targetModel->update($updateData);

            /** @var Revisionable $fresh */
            $fresh = $targetModel->fresh() ?? $target;

            return $fresh;
        });
    }

    /**
     * 現在のコンテンツからスナップショット配列を生成する。
     *
     * 未設定のカラムは null で埋めて、in-memory モデルと DB 再ロード後の
     * ハッシュが一致するよう正規化する。
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(Revisionable $target): array
    {
        /** @var Model $model */
        $model = $target;
        $attributes = $model->getAttributes();

        $snapshot = [];
        foreach ($target->revisionableFields() as $field) {
            $snapshot[$field] = $attributes[$field] ?? null;
        }

        return $snapshot;
    }

    /**
     * 保護中のリビジョン件数を返す。
     */
    public function countProtected(Revisionable $target): int
    {
        $revisionClass = $target->revisionModel();

        return $revisionClass::query()
            ->where($target->revisionForeignKey(), $target->getKey())
            ->where('is_protected', true)
            ->count();
    }

    /**
     * 保持件数設定を取得する（0〜MAX_RETENTION にクランプ）。
     */
    public function getRetentionCount(): int
    {
        $value = (int) BaseSetting::getValue(self::SETTING_KEY_RETENTION, self::DEFAULT_RETENTION);

        return max(0, min(self::MAX_RETENTION, $value));
    }

    /**
     * 直前リビジョンのスナップショットと現在のスナップショットが同一かを判定する。
     *
     * @param  array<string, mixed>  $snapshot
     */
    private function matchesLatest(Revisionable $target, array $snapshot): bool
    {
        $revisionClass = $target->revisionModel();
        $latest = $revisionClass::query()
            ->where($target->revisionForeignKey(), $target->getKey())
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
     * 保持件数を超えた非保護リビジョンを古い順に削除する。
     *
     * 保護されたリビジョンは対象外のため、結果的に総件数が保持件数を
     * 上回ることがあるが、ユーザーの明示的な保護意図を尊重する。
     */
    private function pruneOldRevisions(Revisionable $target, int $retention): void
    {
        $revisionClass = $target->revisionModel();
        $fk = $target->revisionForeignKey();
        $parentId = $target->getKey();

        $total = $revisionClass::query()
            ->where($fk, $parentId)
            ->count();

        $overflow = $total - $retention;
        if ($overflow <= 0) {
            return;
        }

        $ids = $revisionClass::query()
            ->where($fk, $parentId)
            ->where('is_protected', false)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($overflow)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            $revisionClass::query()->whereIn('id', $ids)->delete();
        }
    }

    /**
     * リビジョンモデルから親 Revisionable を取得する。
     */
    private function loadTarget(Model $revision): Revisionable
    {
        /** @var Revisionable|null $target */
        $target = null;

        // リビジョンモデルに定義されたリレーションメソッドのうち、
        // Revisionable を返すものを探す。下記は優先順位付きの候補リスト。
        // 汎用名（content/target/revisionable）を最優先にし、
        // その後にコアおよび各プラグインが使う想定のリレーション名を並べる。
        foreach (self::revisionableRelationCandidates() as $relation) {
            if (method_exists($revision, $relation)) {
                $resolved = $revision->{$relation};
                if ($resolved instanceof Revisionable) {
                    $target = $resolved;
                    break;
                }
            }
        }

        if (! $target) {
            throw new \RuntimeException(
                'Could not resolve Revisionable target from revision model '.get_class($revision).
                '. Define a relation returning a Revisionable instance, '.
                'named one of: '.implode(', ', self::revisionableRelationCandidates()).'.'
            );
        }

        return $target;
    }

    /**
     * 親 Revisionable を逆引きするために探索するリレーション名の候補リスト。
     *
     * プラグインが別の命名を使う場合は、そのリレーション名を下記に追加するか、
     * `RevisionService` を継承して `loadTarget()` を上書きすること。
     *
     * @return list<string>
     */
    private static function revisionableRelationCandidates(): array
    {
        return [
            // 汎用名（優先）
            'content',
            'target',
            'revisionable',
            // コア
            'frontPage',
            // プラグイン用の想定名
            'page',     // DixlasePages
            'post',     // 将来の DixlaseBlog
            'legal',    // DixlaseLegal
            'article',
            'entry',
        ];
    }
}
