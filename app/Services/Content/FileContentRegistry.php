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
use App\Support\Content\FileContentColumns;
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
                    $publisher->check($model);
                    $checked++;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return ['checked' => $checked, 'pending' => $this->pendingCount()];
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
                $count += $modelClass::fileStoredQuery()
                    ->whereNotNull(FileContentColumns::FILE_HASH)
                    ->where(function ($query) {
                        $query->whereNull(FileContentColumns::PUBLISHED_HASH)
                            ->orWhereColumn(FileContentColumns::FILE_HASH, '!=', FileContentColumns::PUBLISHED_HASH);
                    })
                    ->count();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $count;
    }
}
