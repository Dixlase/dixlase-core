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

namespace App\Services;

use App\Contracts\Revisionable;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Accepts models implementing `Revisionable` (Core, plugin, or theme) and provides the following:
 *   - Snapshot recording on save (skipped if no diff)
 *   - Auto-deletion when retention count is exceeded (excluding protected flags)
 *   - Restoration (creates pre-restore backup if needed)
 *   - Diff detection using normalized hash
 *
 * Usage example:
 * ```php
 * $service = app(RevisionService::class);
 * $service->record($frontPage, type: 'manual', userId: $actor->getActorId());
 * $service->restore($revision, userId: $actor->getActorId());
 * ```
 *
 * The retention count is retrieved from the settings key `content.revision.retention_count` common to all content types
 * If a plugin wants to have its own retention policy, inherit this service and
 * override `getRetentionCount()`
 */
class RevisionService
{
    public const TYPE_AUTO = 'auto';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_RESTORE_BACKUP = 'restore_backup';

    /** Settings key: number of revisions to retain (0 disables revisions, max MAX_RETENTION) */
    public const SETTING_KEY_RETENTION = 'content.revision.retention_count';

    public const DEFAULT_RETENTION = 50;

    public const MAX_RETENTION = 500;

    /**
     * Create a revision
     *
     * Skip creation if content is identical to the previous revision
     * After creation, auto-delete unprotected revisions exceeding retention count in oldest-first order
     *
     * @return Model|null Created revision model (null if skipped)
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
     * Restore content with the specified revision's content
     *
     * Only if the current state before restoration differs from the previous revision,
     * create one automatic backup of `TYPE_RESTORE_BACKUP`
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
     * Generate a snapshot array from the current content
     *
     * Fill unset columns with null so that in-memory models and post-DB-reload
     * Normalize to match hashes
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
     * Return the count of protected revisions
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
     * Retrieve retention count settings (clamped to 0–MAX_RETENTION)
     */
    public function getRetentionCount(): int
    {
        $value = (int) SiteSetting::getValue(self::SETTING_KEY_RETENTION, self::DEFAULT_RETENTION);

        return max(0, min(self::MAX_RETENTION, $value));
    }

    /**
     * Determine if the previous revision snapshot and the current snapshot are identical
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
     * Return the normalized hash of the snapshot array
     *
     * @param  array<string, mixed>  $snapshot
     */
    private function hash(array $snapshot): string
    {
        // Values such as enum instances and datetime that do not match the type of JSON from DB
        // are normalized to primitives by round-tripping through JSON before comparison
        $normalized = json_decode(
            (string) json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            true
        ) ?? [];
        ksort($normalized);

        return hash('sha256', (string) json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Delete unprotected revisions exceeding the retention count, oldest first
     *
     * Protected revisions are excluded, so the total count may
     * exceed the retention count, but we respect the user's explicit protection intent
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
     * Retrieve the parent Revisionable from the revision model
     */
    private function loadTarget(Model $revision): Revisionable
    {
        /** @var Revisionable|null $target */
        $target = null;

        // Among the relation methods defined in the revision model,
        // search for those that return Revisionable. The following is a prioritized candidate list
        // Prioritize generic names (content/target/revisionable) first,
        // followed by relation names expected to be used by Core and each plugin
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
     * Candidate list of relation names to search for reverse-lookup of parent Revisionable
     *
     * If a plugin uses a different naming, add that relation name below, or
     * extend `RevisionService` and override `loadTarget()`
     *
     * @return list<string>
     */
    private static function revisionableRelationCandidates(): array
    {
        return [
            // Generic names (priority)
            'content',
            'target',
            'revisionable',
            // Core
            'frontPage',
            // Expected name for plugin
            'page',     // DixlasePages
            'post',     // Future DixlaseBlog
            'legal',    // DixlaseLegal
            'article',
            'entry',
        ];
    }
}
