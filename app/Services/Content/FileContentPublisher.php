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

use App\Actions\Content\RecordFileContentChangeAction;
use App\Actors\SystemActor;
use App\Contracts\Content\DeclaresScriptCapableParts;
use App\Contracts\Content\PublishesFileContent;
use App\Enums\ContentPublishMode;
use App\Services\SchedulerHeartbeat;
use App\Services\Security\ScheduledSecurityCheckMonitor;
use App\Support\Content\FileContentColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Publish file-stored content through the admin
 *
 * The published copy of file-stored content is the one in the database. This
 * service compares it with the files, records a change made to the files
 * outside the admin, takes the files in when they are published, and writes
 * the files back from the database after a revision restore. Nothing is taken
 * in without someone publishing it: under
 * {@see \App\Enums\ContentPublishMode::FILE_SAVE} in a local environment
 * ({@see fileSaveModeAvailable()}) the site shows the files at once, but the
 * change still waits to be published (#808).
 *
 * Every part is compared as a string; a missing file counts as empty.
 */
class FileContentPublisher
{
    /**
     * The part names taken to carry a script when the owner does not say
     *
     * See {@see scriptCapableParts()} for why a body is not in this list.
     *
     * @var list<string>
     */
    public const DEFAULT_SCRIPT_CAPABLE_PARTS = ['custom_css', 'custom_js'];

    /**
     * A change was noticed while a page or an edit screen was being read
     */
    public const DETECTED_BY_RENDER = 'render';

    /**
     * A change was noticed by the scheduled integrity scan
     *
     * Worth telling apart from a render: with cron off, nothing finds a
     * change until somebody looks at the page.
     */
    public const DETECTED_BY_SCAN = 'scan';

    /**
     * The value of a part to render
     *
     * Looks at the files first, so a change made outside the admin is
     * recorded on the first read that sees it. Under the admin mode the
     * published copy is returned whatever the files hold. Under the file save
     * mode, in effect only in a local environment, the files are returned, so
     * an edit shows at once -- while the published copy stays as it was and
     * the change waits to be published like any other (#808).
     *
     * @param  PublishesFileContent&Model  $model
     */
    public function resolve(PublishesFileContent $model, string $part): ?string
    {
        $this->check($model);

        if ($model->usesFileStorage()
            && $this->effectivePublishMode($model) === ContentPublishMode::FILE_SAVE) {
            $files = $this->readFiles($model);

            if (array_key_exists($part, $files)) {
                return $files[$part];
            }
        }

        $column = $model->fileContentParts()[$part] ?? $part;
        $value = $model->getAttributes()[$column] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Compare the files with what was last seen, and record a change once
     *
     * @param  PublishesFileContent&Model  $model
     * @param  string  $detectedBy  How this check came about, recorded in the
     *                              audit entry: {@see DETECTED_BY_RENDER} when
     *                              a page or an edit screen was being read,
     *                              {@see DETECTED_BY_SCAN} from the scheduled
     *                              scan. The default suits a render, which is
     *                              every call site but the scan.
     */
    public function check(PublishesFileContent $model, string $detectedBy = self::DETECTED_BY_RENDER): void
    {
        if (! $model->usesFileStorage()) {
            return;
        }

        $files = $this->readFiles($model);
        $fileHash = self::hash($files);
        $publishedHash = $this->publishedHash($model);

        if ($fileHash === $publishedHash) {
            if ($model->getAttribute(FileContentColumns::FILE_HASH) !== $fileHash) {
                // The files went back to the published copy, so a change
                // recorded earlier is no longer waiting.
                $this->storeHashes($model, $fileHash, $fileHash);
                $this->syncPendingAlert();
            }

            return;
        }

        if ($model->getAttribute(FileContentColumns::FILE_HASH) === $fileHash) {
            // Already recorded, so there is nothing new to say. The change
            // keeps the two hashes apart until it is published, and check()
            // runs on every render of the page.
            return;
        }

        (new RecordFileContentChangeAction($model, $this, $files, $detectedBy, $publishedHash))
            ->execute(new SystemActor('file-content'), []);

        $this->syncPendingAlert();
    }

    /**
     * The parts whose files differ from the published copy
     *
     * @param  PublishesFileContent&Model  $model
     * @return array<string, array{published: string, file: string}>
     */
    public function pendingChanges(PublishesFileContent $model): array
    {
        if (! $model->usesFileStorage()) {
            return [];
        }

        $published = $this->publishedParts($model);
        $changes = [];

        foreach ($this->readFiles($model) as $part => $file) {
            if ($file !== $published[$part]) {
                $changes[$part] = ['published' => $published[$part], 'file' => $file];
            }
        }

        return $changes;
    }

    /**
     * Take the files in as the published copy
     *
     * @param  PublishesFileContent&Model  $model
     * @return list<string> The parts that changed
     */
    public function publish(PublishesFileContent $model): array
    {
        $files = $this->readFiles($model);
        $changed = $this->changedParts($model, $files);

        $values = [];
        foreach ($model->fileContentParts() as $part => $column) {
            $values[$column] = $files[$part];
        }
        $model->forceFill($values)->save();

        $hash = self::hash($files);
        $this->storeHashes($model, $hash, $hash);
        $this->syncPendingAlert();

        return $changed;
    }

    /**
     * Record that the admin has just saved the record
     *
     * The admin writes the published copy and the files together, so both
     * hashes move to the saved state.
     *
     * @param  PublishesFileContent&Model  $model
     */
    public function markSaved(PublishesFileContent $model): void
    {
        $published = self::hash($this->publishedParts($model));
        $file = $model->usesFileStorage() ? self::hash($this->readFiles($model)) : null;

        $this->storeHashes($model, $published, $file);
        $this->syncPendingAlert();
    }

    /**
     * Write the published copy back to the files
     *
     * Used after a revision restore, which changes only the database, so
     * that the files do not keep the content the restore replaced.
     *
     * @param  PublishesFileContent&Model  $model
     */
    public function writeBack(PublishesFileContent $model): void
    {
        if (! $model->usesFileStorage()) {
            $this->markSaved($model);

            return;
        }

        $disk = Storage::disk($model->fileContentDisk());
        $published = $this->publishedParts($model);

        foreach ($published as $part => $value) {
            $path = $model->fileContentPath($part);

            if ($value === '') {
                $disk->delete($path);
            } else {
                $disk->put($path, $value);
            }
        }

        $hash = self::hash($published);
        $this->storeHashes($model, $hash, $hash);
        $this->syncPendingAlert();
    }

    /**
     * Raise or clear the admin-wide alert for changes waiting to be published
     *
     * The alert is raised, with one mail, when the first change is recorded
     * and cleared once nothing is pending. Every place that moves the hashes
     * calls this, so the alert follows the records rather than a schedule.
     * A failure is reported and never breaks the page or the save.
     *
     * @param  int|null  $pending  The pending count when the caller already
     *                             has it; counted here otherwise
     */
    public function syncPendingAlert(?int $pending = null): void
    {
        try {
            $pending ??= app(FileContentRegistry::class)->pendingCount();
            app(ScheduledSecurityCheckMonitor::class)->recordFileContentPending($pending);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Whether the scheduler is running, so that a change is found without
     * anyone reading a page
     *
     * Without it, a change to the files is found only when a page or an edit
     * screen that shows the record is read. A screen that sets a publish mode
     * says so when this is false; see `<x-content.scheduler-notice>`.
     */
    public function schedulerRunning(): bool
    {
        return app(SchedulerHeartbeat::class)->status()['status'] !== SchedulerHeartbeat::STATUS_STOPPED;
    }

    /**
     * Read every part's file; a missing file reads as empty
     *
     * @param  PublishesFileContent&Model  $model
     * @return array<string, string>
     */
    public function readFiles(PublishesFileContent $model): array
    {
        $disk = Storage::disk($model->fileContentDisk());
        $files = [];

        foreach (array_keys($model->fileContentParts()) as $part) {
            $path = $model->fileContentPath($part);
            $files[$part] = $disk->exists($path) ? (string) $disk->get($path) : '';
        }

        return $files;
    }

    /**
     * Every part's published copy, from the database
     *
     * @param  PublishesFileContent&Model  $model
     * @return array<string, string>
     */
    public function publishedParts(PublishesFileContent $model): array
    {
        $parts = [];

        // The raw column, not getAttribute(): a translatable model answers
        // getAttribute() with the current locale's translation.
        $attributes = $model->getAttributes();

        foreach ($model->fileContentParts() as $part => $column) {
            $parts[$part] = (string) ($attributes[$column] ?? '');
        }

        return $parts;
    }

    /**
     * The parts of the given file contents that differ from the published copy
     *
     * @param  PublishesFileContent&Model  $model
     * @param  array<string, string>  $files
     * @return list<string>
     */
    public function changedParts(PublishesFileContent $model, array $files): array
    {
        $published = $this->publishedParts($model);

        return array_values(array_filter(
            array_keys($files),
            fn (string $part) => $files[$part] !== ($published[$part] ?? ''),
        ));
    }

    /**
     * The parts of this record that may carry a script
     *
     * An owner says so itself by implementing
     * {@see DeclaresScriptCapableParts}. Otherwise the answer comes from the
     * part's name: `custom_css` and `custom_js` are the conventional names
     * for the two parts that are a script or can introduce one, and a record
     * that uses them means them. A body is not assumed either way, because
     * whether it can carry a script depends on its editor type, which only
     * the owner knows.
     *
     * Since #786 this no longer decides what the file save mode takes in:
     * that mode takes every part in a local environment and nothing
     * elsewhere. It is kept for owners that already declare their parts and
     * for screens that say which parts can carry a script.
     *
     * @param  PublishesFileContent&Model  $model
     * @return list<string>
     */
    public function scriptCapableParts(PublishesFileContent $model): array
    {
        $parts = array_keys($model->fileContentParts());

        if ($model instanceof DeclaresScriptCapableParts) {
            $declared = $model->scriptCapableFileContentParts();

            return array_values(array_intersect($parts, $declared));
        }

        return array_values(array_intersect($parts, self::DEFAULT_SCRIPT_CAPABLE_PARTS));
    }

    /**
     * Whether the file save mode takes effect in this environment
     *
     * Showing the files without anyone publishing them is a development
     * convenience, so it applies only in a local environment (#786). There
     * whoever can write the files runs the code as well. Anywhere else --
     * staging, production, a mistyped APP_ENV -- the site shows the published
     * copy, whatever the owner's setting says. In every environment a change
     * to the files waits to be published (#808). The check is "is
     * local", never "is not production", so an unknown value stays closed.
     */
    public static function fileSaveModeAvailable(): bool
    {
        return app()->environment('local');
    }

    /**
     * The publish mode that takes effect for this record here
     *
     * The owner's setting, except that the file save mode falls back to the
     * admin mode outside a local environment. The setting itself is left as
     * chosen, so a copy of the site in a local environment picks it up again.
     *
     * @param  PublishesFileContent&Model  $model
     */
    public function effectivePublishMode(PublishesFileContent $model): ContentPublishMode
    {
        $mode = $model->fileContentPublishMode();

        if ($mode === ContentPublishMode::FILE_SAVE && ! self::fileSaveModeAvailable()) {
            return ContentPublishMode::ADMIN;
        }

        return $mode;
    }

    /**
     * The changed parts this record's publish mode may take in by itself
     *
     * Always empty since #808: no mode takes a change in without someone
     * publishing it. The file save mode shows the files instead
     * ({@see resolve()}), and the change waits to be published.
     *
     * @deprecated Kept for callers outside core; it no longer depends on its
     *             arguments.
     *
     * @param  PublishesFileContent&Model  $model
     * @param  array<string, string>  $files
     * @return list<string>
     */
    public function takeableParts(PublishesFileContent $model, array $files): array
    {
        return [];
    }

    /**
     * Store both hashes without touching the record's timestamps or events
     *
     * @param  PublishesFileContent&Model  $model
     */
    public function storeHashes(PublishesFileContent $model, ?string $published, ?string $file): void
    {
        $values = [
            FileContentColumns::PUBLISHED_HASH => $published,
            FileContentColumns::FILE_HASH => $file,
        ];

        $model->getConnection()
            ->table($model->getTable())
            ->where($model->getKeyName(), $model->getKey())
            ->update($values);

        $model->forceFill($values)->syncOriginalAttributes(array_keys($values));
    }

    /**
     * Hash a set of parts
     *
     * @param  array<string, string>  $parts
     */
    public static function hash(array $parts): string
    {
        ksort($parts);

        return hash('sha256', json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * The stored hash of the published copy, or one computed from the columns
     * when none has been stored yet
     *
     * @param  PublishesFileContent&Model  $model
     */
    private function publishedHash(PublishesFileContent $model): string
    {
        $stored = $model->getAttribute(FileContentColumns::PUBLISHED_HASH);

        return is_string($stored) && $stored !== '' ? $stored : self::hash($this->publishedParts($model));
    }
}
