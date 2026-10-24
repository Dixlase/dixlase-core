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
use App\Support\Content\FileContentColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Publish file-stored content through the admin
 *
 * The published copy of file-stored content is the one in the database. This
 * service compares it with the files, records a change made to the files
 * outside the admin, takes the files in when they are published (or at once,
 * under {@see \App\Enums\ContentPublishMode::FILE_SAVE}), and writes the
 * files back from the database after a revision restore.
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
     * published copy is returned whatever the files hold; under the file
     * save mode the files have been taken in by then, so the column holds
     * them.
     *
     * @param  PublishesFileContent&Model  $model
     */
    public function resolve(PublishesFileContent $model, string $part): ?string
    {
        $this->check($model);

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
                $this->storeHashes($model, $fileHash, $fileHash);
            }

            return;
        }

        if ($model->getAttribute(FileContentColumns::FILE_HASH) === $fileHash
            && $this->takeableParts($model, $files) === []) {
            // Already recorded, and nothing here this mode may take in, so
            // there is nothing new to say. What is takeable is re-asked
            // rather than decided by the mode alone, for two reasons:
            //
            //  - the owner may have switched to the file save mode since the
            //    change was recorded, and the change should then be taken in
            //  - a part held for review keeps the two hashes apart for as
            //    long as it is held. A mode-only guard would therefore let
            //    every later check record the same change again, and check()
            //    runs on every render of the page.
            return;
        }

        (new RecordFileContentChangeAction($model, $this, $files, $detectedBy, $publishedHash))
            ->execute(new SystemActor('file-content'), []);
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
     * The changed parts this record's publish mode may take in by itself
     *
     * Empty under the admin mode: nothing is taken in without someone
     * publishing it. Under the file save mode it is the changed parts that
     * cannot carry a script — the rest wait to be published, so that "only
     * ADMIN and above can put a script on the site" holds for the files too.
     *
     * @param  PublishesFileContent&Model  $model
     * @param  array<string, string>  $files
     * @return list<string>
     */
    public function takeableParts(PublishesFileContent $model, array $files): array
    {
        if ($model->fileContentPublishMode() !== ContentPublishMode::FILE_SAVE) {
            return [];
        }

        $scriptCapable = $this->scriptCapableParts($model);

        return array_values(array_diff($this->changedParts($model, $files), $scriptCapable));
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
