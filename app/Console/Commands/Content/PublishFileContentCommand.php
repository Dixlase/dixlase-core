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

namespace App\Console\Commands\Content;

use App\Actions\Content\PublishFileContentAction;
use App\Actors\SystemActor;
use App\Contracts\Content\ProvidesFileContentEditLink;
use App\Contracts\Content\PublishesFileContent;
use App\Services\Content\FileContentPublisher;
use App\Services\Content\FileContentRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * List or publish the changes waiting in file-stored content (#805)
 *
 * The command-line counterpart of the Publish button on an edit screen, for
 * a site whose content files arrive by deployment. Without options it lists
 * the records whose files differ from the published copy and publishes
 * nothing; `--publish` publishes them through
 * {@see PublishFileContentAction}, so each gets the same revision and audit
 * entry as a publish from the admin, with the console as the actor.
 *
 * `--fail-if-pending` exits non-zero when anything is still waiting at the
 * end, so a deploy can stop on content nobody published.
 */
class PublishFileContentCommand extends Command
{
    protected $signature = 'dls:content:publish-files
                            {--publish : Publish the changes found; without it they are only listed}
                            {--model= : Only this registered model (its class name, with or without the namespace)}
                            {--id= : Only the record with this key (needs --model)}
                            {--fail-if-pending : Exit non-zero when changes are still waiting at the end}';

    protected $description = 'List or publish the changes waiting in file-stored content';

    public function handle(FileContentRegistry $registry, FileContentPublisher $publisher): int
    {
        $models = $this->selectModels($registry);
        if ($models === null) {
            return self::FAILURE;
        }

        $id = $this->option('id');
        if ($id !== null && $this->option('model') === null) {
            $this->error('--id needs --model.');

            return self::FAILURE;
        }

        $publish = (bool) $this->option('publish');
        $actor = new SystemActor('console');
        $found = 0;
        $published = 0;
        $failed = 0;

        foreach ($models as $modelClass) {
            $query = $modelClass::fileStoredQuery();
            if ($id !== null) {
                $query->whereKey($id);
            }

            foreach ($query->cursor() as $record) {
                if (! $record instanceof PublishesFileContent) {
                    continue;
                }

                // Record a change nobody has seen yet, exactly as the
                // scheduled scan does, before deciding what is waiting.
                $publisher->check($record, FileContentPublisher::DETECTED_BY_SCAN);

                $parts = array_keys($publisher->pendingChanges($record));
                if ($parts === []) {
                    continue;
                }
                $found++;

                $line = sprintf('  %s: %s', $this->describe($record), implode(', ', $parts));

                if (! $publish) {
                    $this->line($line);

                    continue;
                }

                try {
                    $result = (new PublishFileContentAction($record, null, $publisher))->execute($actor, []);
                } catch (Throwable $e) {
                    report($e);
                    $result = null;
                }

                if ($result?->success) {
                    $published++;
                    $this->line($line.' — published');
                } else {
                    $failed++;
                    $this->error($line.' — failed'.($result?->message ? ': '.$result->message : ''));
                }
            }
        }

        $publisher->syncPendingAlert();

        if ($found === 0) {
            $this->info('No file-stored content is waiting to be published.');

            return self::SUCCESS;
        }

        $this->info($publish
            ? sprintf('%d record(s) published, %d failed.', $published, $failed)
            : sprintf('%d record(s) have file changes waiting to be published. Run with --publish to publish them.', $found));

        $waiting = $found - $published;
        if ($failed > 0 || ($waiting > 0 && $this->option('fail-if-pending'))) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The registered models to look at, or null when --model matches none
     *
     * @return list<class-string<PublishesFileContent&Model>>|null
     */
    private function selectModels(FileContentRegistry $registry): ?array
    {
        $models = $registry->models();
        $wanted = $this->option('model');

        if ($wanted === null) {
            return $models;
        }

        $wanted = ltrim((string) $wanted, '\\');
        $matches = array_values(array_filter(
            $models,
            fn (string $class) => strcasecmp($class, $wanted) === 0 || strcasecmp(class_basename($class), $wanted) === 0,
        ));

        if ($matches === []) {
            $this->error(sprintf('No registered model matches "%s". Registered: %s', $wanted, implode(', ', $models) ?: '(none)'));

            return null;
        }

        return $matches;
    }

    /**
     * A record as a reader would name it
     */
    private function describe(Model $record): string
    {
        $name = class_basename($record).' #'.$record->getKey();

        if ($record instanceof ProvidesFileContentEditLink) {
            try {
                $label = $record->fileContentLabel();
                if ($label !== '') {
                    $name .= ' ('.$label.')';
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $name;
    }
}
