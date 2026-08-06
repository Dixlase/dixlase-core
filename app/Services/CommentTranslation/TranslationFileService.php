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

namespace App\Services\CommentTranslation;

use Illuminate\Support\Facades\File;

/**
 * @api
 *
 * Manages reading and writing of comment translation files.
 *
 * Translation files use a flat PHP-array format `'JP' => 'EN'`. Metadata
 * (review status, glossary lookups, etc.) is stored as keys prefixed with
 * an underscore (e.g. `_review_status`) and segregated by helper methods
 * so the translation entries themselves stay clean.
 */
class TranslationFileService
{
    /** Special filename inside the storage path; not a translation file. */
    public const GLOSSARY_FILENAME = '_glossary.php';

    /** Metadata key under which review status is stored. */
    public const META_REVIEW_STATUS = '_review_status';

    /** Allowed review status values. */
    public const REVIEW_STATUSES = ['untranslated', 'machine', 'reviewed', 'human'];

    /** @var string Base path where translation files live (per-locale). */
    protected string $storagePath = '';

    /** @var string Currently active locale (sub-directory under storage_path). */
    protected string $locale = 'ja';

    public function __construct()
    {
        $configured = config('comment-translation.default_locale');
        if (is_string($configured) && $configured !== '') {
            $this->locale = $configured;
        }
        $this->refreshStoragePath();
    }

    /**
     * Switch the active locale. Subsequent path lookups will resolve under
     * the new locale's sub-directory (e.g. resources/comment-translations/zh).
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->refreshStoragePath();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Recompute the per-locale storage path. The base path is resolved
     * against the Core project root via `base_path()`, with a fallback
     * to `dirname(__DIR__, 3)` when called outside a Laravel context.
     */
    protected function refreshStoragePath(): void
    {
        // app/Services/CommentTranslation/ -> three levels up is the Core root.
        $base = function_exists('base_path')
            ? base_path()
            : dirname(__DIR__, 3);

        $relative = config(
            'comment-translation.storage_path',
            'resources/comment-translations'
        );

        $this->storagePath = $base.'/'.$relative.'/'.$this->locale;
    }

    /**
     * Build the translation file path for a given source file.
     */
    public function getTranslationPath(string $sourceFile): string
    {
        $relativePath = $this->normalizeSourcePath($sourceFile);

        return $this->storagePath.'/'.$relativePath;
    }

    /**
     * Load the entire translation file as-is (entries + metadata).
     *
     * @return array<string, mixed>
     */
    public function load(string $translationFile): array
    {
        if (! File::exists($translationFile)) {
            return [];
        }

        $loaded = require $translationFile;

        return is_array($loaded) ? $loaded : [];
    }

    /**
     * Load only the translation entries (excludes underscore-prefixed metadata).
     *
     * @return array<string, string>
     */
    public function loadTranslations(string $translationFile): array
    {
        $entries = [];
        foreach ($this->load($translationFile) as $key => $value) {
            if (! is_string($key) || $key === '' || $key[0] === '_') {
                continue;
            }
            $entries[$key] = is_string($value) ? $value : '';
        }

        return $entries;
    }

    /**
     * Load only the metadata entries (underscore-prefixed keys).
     *
     * @return array<string, mixed>
     */
    public function loadMeta(string $translationFile): array
    {
        $meta = [];
        foreach ($this->load($translationFile) as $key => $value) {
            if (is_string($key) && $key !== '' && $key[0] === '_') {
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    /**
     * Load the per-entry review status map for a translation file.
     *
     * @return array<string, string> Japanese key => status (untranslated/machine/reviewed/human)
     */
    public function loadReviewStatus(string $translationFile): array
    {
        $meta = $this->loadMeta($translationFile);
        $status = $meta[self::META_REVIEW_STATUS] ?? [];

        return is_array($status) ? $status : [];
    }

    /**
     * Load the project-wide glossary used to anchor terminology in AI translations.
     *
     * @return array<string, string> Japanese term => canonical English rendering
     */
    public function loadGlossary(): array
    {
        $path = $this->storagePath.'/'.self::GLOSSARY_FILENAME;

        if (! File::exists($path)) {
            return [];
        }

        $loaded = require $path;

        return is_array($loaded) ? $loaded : [];
    }

    /**
     * Write a translation file with translations and (optional) metadata.
     *
     * @param  array<string, string>  $translations  Japanese key => English value
     * @param  array<string, mixed>  $meta  Underscore-prefixed metadata keys
     */
    public function save(string $translationFile, array $translations, array $meta = []): void
    {
        $directory = dirname($translationFile);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $content = "<?php\n\nreturn [\n";

        foreach ($translations as $key => $value) {
            $escapedKey = $this->escapePhpString($key);
            $escapedValue = $this->escapePhpString((string) $value);
            $content .= "    '{$escapedKey}' => '{$escapedValue}',\n";
        }

        if ($meta !== []) {
            $content .= "\n    // ----- metadata (underscore-prefixed; ignored as translation entries) -----\n";
            foreach ($meta as $key => $value) {
                $content .= '    '.$this->encodePhp($key).' => '.$this->encodePhp($value, 1).",\n";
            }
        }

        $content .= "];\n";

        File::put($translationFile, $content);
    }

    /**
     * Merge a fresh extraction with the existing translation file.
     *
     * The dictionary uses an English-keyed format for translated entries
     * (`'EN canonical' => 'JP archive'`) and a transient Japanese-keyed
     * format for pending entries (`'JP text' => ''`). This merge accepts a
     * fresh list of Japanese comments scanned from the source and:
     *
     * 1. Preserves any translated entry whose JP value is still present in
     *    the source (the EN → JP pair stays intact).
     * 2. Preserves any pending entry (JP key, empty value) that is still
     *    present in the source.
     * 3. Drops translated and pending entries whose Japanese text has
     *    disappeared from the source (developer rewrote / removed it).
     * 4. Adds a fresh `[JP => '']` pending entry for every Japanese comment
     *    that the existing file did not already know about.
     * 5. Prunes `_review_status` entries whose EN key no longer maps to a
     *    translated entry in the merged dictionary.
     *
     * @param  array<string, mixed>  $existing  Full prior file contents (entries + metadata)
     * @param  array<string, string>  $new  Freshly extracted JP texts (`['JP1' => '', 'JP2' => '', ...]`)
     * @return array{0: array<string, string>, 1: array<string, mixed>} [merged_entries, merged_meta]
     */
    public function merge(array $existing, array $new): array
    {
        $existingTranslations = [];
        $existingMeta = [];
        foreach ($existing as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }
            if ($key[0] === '_') {
                $existingMeta[$key] = $value;
            } else {
                $existingTranslations[$key] = is_string($value) ? $value : '';
            }
        }

        // Index existing entries by the Japanese text they refer to, so we can
        // detect "this JP comment is already known" regardless of whether the
        // entry is translated (EN-keyed) or pending (JP-keyed).
        // For translated entries, the JP text lives in the value.
        // For pending entries (empty value), the JP text IS the key.
        $jpToEntry = [];
        foreach ($existingTranslations as $key => $value) {
            $jp = $value !== '' ? $value : $key;
            // First-write-wins if duplicate JP shows up; in practice extractor dedupes.
            $jpToEntry[$jp] ??= ['key' => $key, 'value' => $value];
        }

        // Additive merge: preserve ALL existing entries (the dictionary is
        // a permanent archive of every JP comment we've ever seen — once a
        // source file is translated to English, the JP archive must
        // survive so the JA release build can reverse-translate). Only
        // ADD new pending entries for JP texts we haven't seen before.
        $merged = $existingTranslations;
        $newJpTexts = array_keys($new);

        foreach ($newJpTexts as $jp) {
            if (! isset($jpToEntry[$jp]) && ! array_key_exists($jp, $merged)) {
                $merged[$jp] = '';
            }
        }

        return [$merged, $existingMeta];
    }

    /**
     * Replace a pending entry (`[JP => '']`) with a translated one
     * (`[EN => JP]`) and mark the new EN key with the given review status.
     *
     * Used by `dls:comment:translate` after a successful Claude API call,
     * so that the on-disk format is always EN-keyed for any entry that has
     * a value.
     *
     * @param  array<string, string>  $entries  Mutable translations array (will be modified in place)
     * @param  array<string, mixed>  $meta  Mutable metadata array
     * @param  string  $jp  Original Japanese (pending key being replaced)
     * @param  string  $en  English translation that becomes the new key
     * @param  string  $status  Review status to record (default 'machine')
     */
    public function rotatePendingToTranslated(
        array &$entries,
        array &$meta,
        string $jp,
        string $en,
        string $status = 'machine',
    ): void {
        unset($entries[$jp]);
        $entries[$en] = $jp;

        $reviewStatus = is_array($meta[self::META_REVIEW_STATUS] ?? null)
            ? $meta[self::META_REVIEW_STATUS]
            : [];
        $reviewStatus[$en] = $status;
        $meta[self::META_REVIEW_STATUS] = $reviewStatus;
    }

    /**
     * Get the base storage path.
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Get all translation file paths under the active locale's storage
     * path (excludes the glossary).
     *
     * @return array<string>
     */
    public function getAllTranslationFiles(): array
    {
        return $this->getAllTranslationFilesIn($this->storagePath);
    }

    /**
     * Get all translation file paths under an arbitrary dictionary root
     * (excludes that root's `_glossary.php`). Used when scanning extra
     * dictionary roots — e.g. plugin/theme dicts located via the
     * ExtensionDictionaryLocator.
     *
     * @return array<string>
     */
    public function getAllTranslationFilesIn(string $rootDir): array
    {
        if (! File::isDirectory($rootDir)) {
            return [];
        }

        $glossaryPath = rtrim($rootDir, '/').'/'.self::GLOSSARY_FILENAME;

        return collect(File::allFiles($rootDir))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->filter(fn ($file) => $file->getPathname() !== $glossaryPath)
            ->map(fn ($file) => $file->getPathname())
            ->values()
            ->all();
    }

    /**
     * ソースパスを正規化（プロジェクトルートからの相対パスに変換）
     */
    protected function normalizeSourcePath(string $sourceFile): string
    {
        $basePath = base_path().'/';

        if (str_starts_with($sourceFile, $basePath)) {
            return substr($sourceFile, strlen($basePath));
        }

        return $sourceFile;
    }

    /**
     * Escape a string for embedding inside single-quoted PHP literals.
     */
    protected function escapePhpString(string $value): string
    {
        return str_replace(
            ['\\', "'"],
            ['\\\\', "\\'"],
            $value
        );
    }

    /**
     * Encode a value as a PHP literal usable in the generated translation file.
     *
     * Supports strings, ints, bools, null, and (recursively) arrays. Arrays
     * are emitted in indented short-array syntax aligned to the surrounding
     * indent level.
     */
    protected function encodePhp(mixed $value, int $indent = 0): string
    {
        if (is_string($value)) {
            return "'".$this->escapePhpString($value)."'";
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }
        if (is_array($value)) {
            if ($value === []) {
                return '[]';
            }
            $pad = str_repeat('    ', $indent + 1);
            $closePad = str_repeat('    ', $indent);
            $isList = array_is_list($value);
            $lines = [];
            foreach ($value as $k => $v) {
                if ($isList) {
                    $lines[] = $pad.$this->encodePhp($v, $indent + 1).',';
                } else {
                    $lines[] = $pad.$this->encodePhp($k, $indent + 1).' => '.$this->encodePhp($v, $indent + 1).',';
                }
            }

            return "[\n".implode("\n", $lines)."\n".$closePad.']';
        }

        return 'null';
    }
}
