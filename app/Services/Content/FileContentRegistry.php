<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Services\Content;

use App\Contracts\Content\PublishesFileContent;
use App\Enums\ContentPublishMode;
use App\Support\Content\FileContentColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * The models whose file-stored content the site watches
 *
 * Core registers its front page; an extension registers its own model from
 * its service provider:
 *
 *   app(FileContentRegistry::class)->register(ExamplePage::class);
 *
 * The scheduled integrity scan checks every registered record, so a change
 * to a file nobody has viewed is found as well, and the dashboard counts the
 * changes waiting to be published.
 */
class FileContentRegistry
{
    /** @var list<class-string<PublishesFileContent&Model>> */
    private array $models = [];

    /**
     * @param  class-string<PublishesFileContent&Model>  $modelClass
     */
    public function register(string $modelClass): void
    {
        if (! is_subclass_of($modelClass, PublishesFileContent::class)) {
            throw new \InvalidArgumentException("[{$modelClass}] does not implement ".PublishesFileContent::class.'.');
        }

        if (! in_array($modelClass, $this->models, true)) {
            $this->models[] = $modelClass;
        }
    }

    /**
     * @return list<class-string<PublishesFileContent&Model>>
     */
    public function models(): array
    {
        return $this->models;
    }

    /**
     * Check every file-stored record of every registered model
     *
     * A model whose table cannot be read (an extension being installed, a
     * migration not yet run) is skipped rather than failing the scan.
     *
     * @return array{checked: int, pending: int}
     */
    public function checkAll(FileContentPublisher $publisher): array
    {
        $checked = 0;

        foreach ($this->models as $modelClass) {
            try {
                foreach ($modelClass::fileStoredQuery()->cursor() as $model) {
                    /** @var PublishesFileContent&Model $model */
                    $publisher->check($model, FileContentPublisher::DETECTED_BY_SCAN);
                    $checked++;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $pending = $this->pendingCount();
        $publisher->syncPendingAlert($pending);

        return ['checked' => $checked, 'pending' => $pending];
    }

    /**
     * How many records hold file changes that have not been published
     *
     * Counts what the last check saw; it reads no files.
     */
    public function pendingCount(): int
    {
        $count = 0;

        foreach ($this->models as $modelClass) {
            try {
                $count += self::pendingQuery($modelClass)->count();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $count;
    }

    /**
     * The records that hold file changes that have not been published
     *
     * Like {@see pendingCount()}, this reflects the last check and reads no
     * files. A model whose table cannot be read is skipped.
     *
     * @param  int  $limit  The most records to return, across all models
     * @return list<PublishesFileContent&Model>
     */
    public function pendingRecords(int $limit): array
    {
        $records = [];

        foreach ($this->models as $modelClass) {
            if (count($records) >= $limit) {
                break;
            }

            try {
                foreach (self::pendingQuery($modelClass)->limit($limit - count($records))->get() as $record) {
                    /** @var PublishesFileContent&Model $record */
                    $records[] = $record;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $records;
    }

    /**
     * @param  class-string<PublishesFileContent&Model>  $modelClass
     * @return Builder<Model>
     */
    private static function pendingQuery(string $modelClass): Builder
    {
        return $modelClass::fileStoredQuery()
            ->whereNotNull(FileContentColumns::FILE_HASH)
            ->where(function ($query) {
                $query->whereNull(FileContentColumns::PUBLISHED_HASH)
                    ->orWhereColumn(FileContentColumns::FILE_HASH, '!=', FileContentColumns::PUBLISHED_HASH);
            });
    }

    /**
     * Whether any registered model stores a record in files
     */
    public function hasFileStoredRecords(): bool
    {
        foreach ($this->models as $modelClass) {
            try {
                if ($modelClass::fileStoredQuery()->exists()) {
                    return true;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return false;
    }

    /**
     * Whether any registered model shows its files on file save here
     *
     * The publish mode is a setting of the owner, not of the record — a front
     * page reads one front setting, an extension reads its own — so the first
     * file-stored record of a model answers for that model, and this costs
     * one row per model rather than a walk over all of them.
     *
     * Answers with the mode in effect, so it is false outside a local
     * environment whatever is chosen (#786).
     */
    public function usesFileSaveMode(): bool
    {
        foreach ($this->models as $modelClass) {
            try {
                $record = $modelClass::fileStoredQuery()->first();
            } catch (Throwable $e) {
                report($e);

                continue;
            }

            if ($record instanceof PublishesFileContent
                && app(FileContentPublisher::class)->effectivePublishMode($record) === ContentPublishMode::FILE_SAVE) {
                return true;
            }
        }

        return false;
    }
}
