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
 * Apply per-locale comment translations to PHP source files.
 *
 * Two output modes:
 *   - copy mode (legacy `dist/en` build): read source, write to a separate
 *     output path. Used by release packaging.
 *   - in-place mode: rewrite the source file directly. Used by
 *     `dls:comment:build --in-place`, the user-facing convert command.
 *
 * Two directions:
 *   - forward (default): replace EN canonical with the locale's archive
 *     value (e.g. EN → JA when running `--locale=ja`).
 *   - reverse: replace the locale's archive value back with EN canonical
 *     (used to revert an in-place conversion).
 *
 * Substitution is **AST-aware**: only comment tokens (`T_COMMENT`,
 * `T_DOC_COMMENT`) are rewritten. String literals containing the same
 * text are NOT touched, so user-facing strings that happen to match a
 * dictionary key remain intact.
 *
 * Dictionary contract:
 *   - Translated entry: `'EN canonical' => 'JP archive'`
 *   - Pending entry:    `'JP text' => ''`  (skipped during apply)
 */
class CommentBuilderService
{
    public function __construct(
        protected TranslationFileService $translationFileService,
    ) {}

    /**
     * Apply dictionary substitutions to a single source file.
     *
     * @param  string  $sourceFile  Source path to read from.
     * @param  string|null  $outputFile  Destination path. Null = in-place.
     * @param  array<string, string>  $translations  Dictionary entries.
     * @param  bool  $reverse  Apply locale → EN direction instead of EN → locale.
     * @param  bool  $dryRun  Compute substitutions but do not write.
     * @return array{translated: int, untranslated: int, modified: bool}
     */
    public function applyFile(
        string $sourceFile,
        ?string $outputFile,
        array $translations,
        bool $reverse = false,
        bool $dryRun = false,
    ): array {
        $original = File::get($sourceFile);
        [$content, $count] = str_ends_with($sourceFile, '.blade.php')
            ? $this->applyToBladeContent($original, $translations, $reverse)
            : $this->applyToContent($original, $translations, $reverse);

        $stats = [
            'translated' => $count,
            'untranslated' => $this->countPending($translations),
            'modified' => $content !== $original,
        ];

        if ($dryRun || ! $stats['modified']) {
            // Even in copy mode, when nothing changed we still copy as-is
            // (so the output tree mirrors the source). In in-place mode
            // there is nothing to do.
            if (! $dryRun && $outputFile !== null && $outputFile !== $sourceFile) {
                $this->ensureDirectory(dirname($outputFile));
                File::put($outputFile, $original);
            }

            return $stats;
        }

        $target = $outputFile ?? $sourceFile;
        $this->ensureDirectory(dirname($target));
        File::put($target, $content);

        return $stats;
    }

    /**
     * Apply dictionary substitutions to in-memory content via PHP token
     * AST. Only comment tokens are rewritten.
     *
     * @param  array<string, string>  $translations
     * @return array{0: string, 1: int} [new content, substitution count]
     */
    public function applyToContent(string $content, array $translations, bool $reverse = false): array
    {
        $pairs = $this->buildSubstitutionPairs($translations, $reverse);

        if (empty($pairs)) {
            return [$content, 0];
        }

        $tokens = @token_get_all($content);
        if ($tokens === false) {
            return [$content, 0];
        }

        $count = 0;
        $rebuilt = '';

        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $newComment = $token[1];
                foreach ($pairs as $search => $replace) {
                    if (str_contains($newComment, $search)) {
                        $newComment = str_replace($search, $replace, $newComment);
                        $count++;
                    }
                }
                $rebuilt .= $newComment;
            } elseif (is_array($token)) {
                $rebuilt .= $token[1];
            } else {
                $rebuilt .= $token;
            }
        }

        return [$rebuilt, $count];
    }

    /**
     * Apply dictionary substitutions to a Blade template's comments.
     *
     * Blade comments live in `{{-- ... --}}` blocks; they sit inside
     * template strings and are NOT visible to PHP's `token_get_all()`.
     * We use a non-greedy regex (with the `s` flag so `.` matches
     * newlines) to find each Blade comment and substitute dictionary
     * entries within it. The dictionary keys/values are expected to
     * already include the `{{-- ... --}}` markers (that is the form the
     * Round 10 extractor produced and matches what dictionaries on disk
     * contain).
     *
     * For consistency with `applyToContent()`, only text inside
     * `{{-- ... --}}` blocks is rewritten — nothing else in the
     * template is touched.
     *
     * @param  array<string, string>  $translations
     * @return array{0: string, 1: int} [new content, substitution count]
     */
    public function applyToBladeContent(string $content, array $translations, bool $reverse = false): array
    {
        $pairs = $this->buildSubstitutionPairs($translations, $reverse);

        if (empty($pairs)) {
            return [$content, 0];
        }

        $count = 0;

        // Apply substitutions globally — but only when the search string
        // appears inside a `{{-- ... --}}` block. We do this by walking
        // each Blade comment region and rewriting it in place.
        $rebuilt = preg_replace_callback(
            '/\{\{--(.+?)--\}\}/s',
            function (array $m) use ($pairs, &$count): string {
                $whole = $m[0];
                foreach ($pairs as $search => $replace) {
                    if (str_contains($whole, $search)) {
                        $whole = str_replace($search, $replace, $whole);
                        $count++;
                    }
                }

                return $whole;
            },
            $content
        );

        if ($rebuilt === null) {
            // PCRE failure (e.g. regex stack overflow); leave content alone.
            return [$content, 0];
        }

        return [$rebuilt, $count];
    }

    /**
     * Build [search => replace] pairs from a raw dictionary.
     *
     * Forward (EN → locale): `'EN' => 'JP'` → `[EN => JP]`
     * Reverse (locale → EN): `'EN' => 'JP'` → `[JP => EN]`
     *
     * Pending entries (empty value) and metadata keys (`_` prefix) are
     * dropped. Pairs are sorted by search-key length DESC so longer
     * matches are tried first, avoiding partial-match anomalies.
     *
     * @param  array<string, string>  $translations
     * @return array<string, string>
     */
    protected function buildSubstitutionPairs(array $translations, bool $reverse): array
    {
        $pairs = [];

        foreach ($translations as $en => $localeText) {
            if (! is_string($en) || $en === '' || $en[0] === '_') {
                continue;
            }
            if (! is_string($localeText) || $localeText === '') {
                continue;
            }

            if ($reverse) {
                $pairs[$localeText] = $en;
            } else {
                $pairs[$en] = $localeText;
            }
        }

        uksort($pairs, fn ($a, $b) => strlen($b) - strlen($a));

        return $pairs;
    }

    /**
     * Count pending (untranslated) entries in a dictionary.
     *
     * @param  array<string, string>  $translations
     */
    protected function countPending(array $translations): int
    {
        $pending = 0;
        foreach ($translations as $key => $value) {
            if (! is_string($key) || $key === '' || $key[0] === '_') {
                continue;
            }
            if ($value === '') {
                $pending++;
            }
        }

        return $pending;
    }

    /**
     * Ensure a directory exists, creating it if needed.
     */
    protected function ensureDirectory(string $directory): void
    {
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    // -----------------------------------------------------------------
    // Extension-scoped walkers (core / plugins / themes via locator)
    // -----------------------------------------------------------------

    /**
     * Apply translations across a single extension (core, plugin, or theme).
     *
     * Source files are walked under `$sourceRoot/$scanRelativePath` and
     * matched against translation files under `$dictRoot/<same relative
     * path>`. Dict file paths are computed locally (not via the
     * TranslationFileService instance) so the same builder can serve
     * core, plugins, and themes from one invocation.
     *
     * @param  string  $sourceRoot  Absolute path of the extension root (e.g. base_path() for core).
     * @param  string  $dictRoot  Absolute path of the per-locale dict root for this extension.
     * @param  string  $scanRelativePath  Sub-path under sourceRoot to scan (e.g. "app").
     * @param  string|null  $outputRoot  Output root mirroring sourceRoot (null = in-place).
     * @return array{files: int, translated: int, untranslated: int}
     */
    public function applyExtension(
        string $sourceRoot,
        string $dictRoot,
        string $scanRelativePath,
        ?string $outputRoot = null,
        bool $reverse = false,
        bool $dryRun = false,
    ): array {
        $totals = ['files' => 0, 'translated' => 0, 'untranslated' => 0];

        $scanRoot = rtrim($sourceRoot, '/').'/'.ltrim($scanRelativePath, '/');
        if (! File::isDirectory($scanRoot)) {
            return $totals;
        }

        $sourceRootClean = rtrim($sourceRoot, '/');
        $dictRootClean = rtrim($dictRoot, '/');

        $files = collect(File::allFiles($scanRoot))
            ->filter(fn ($file) => $file->getExtension() === 'php');

        foreach ($files as $file) {
            $sourceFile = $file->getPathname();
            $relativeFromExtension = ltrim(substr($sourceFile, strlen($sourceRootClean)), '/');
            $dictFile = $dictRootClean.'/'.$relativeFromExtension;

            $outputFile = null;
            if ($outputRoot !== null) {
                $outputFile = rtrim($outputRoot, '/').'/'.$relativeFromExtension;
            }

            $translations = $this->translationFileService->loadTranslations($dictFile);

            if (empty($translations)) {
                if ($outputFile !== null && ! $dryRun) {
                    $this->ensureDirectory(dirname($outputFile));
                    File::copy($sourceFile, $outputFile);
                }
                $totals['files']++;

                continue;
            }

            $stats = $this->applyFile($sourceFile, $outputFile, $translations, $reverse, $dryRun);

            $totals['files']++;
            $totals['translated'] += $stats['translated'];
            $totals['untranslated'] += $stats['untranslated'];
        }

        return $totals;
    }

    // -----------------------------------------------------------------
    // Backwards-compatible directory walkers (used by `--output=` mode)
    // -----------------------------------------------------------------

    /**
     * Build a translated copy of the source at a separate output path.
     *
     * Files without a dictionary entry are copied verbatim (mirror mode).
     * The active locale comes from the injected TranslationFileService.
     *
     * @return array{files: int, translated: int, untranslated: int}
     */
    public function buildDirectory(string $sourcePath, string $outputPath): array
    {
        return $this->walkDirectory($sourcePath, $outputPath, reverse: false, dryRun: false);
    }

    /**
     * Apply translations in-place across a directory tree.
     *
     * @return array{files: int, translated: int, untranslated: int}
     */
    public function applyDirectoryInPlace(string $sourcePath, bool $reverse = false, bool $dryRun = false): array
    {
        return $this->walkDirectory($sourcePath, outputPath: null, reverse: $reverse, dryRun: $dryRun);
    }

    /**
     * Internal walker shared by both modes.
     *
     * @return array{files: int, translated: int, untranslated: int}
     */
    protected function walkDirectory(string $sourcePath, ?string $outputPath, bool $reverse, bool $dryRun): array
    {
        $totals = ['files' => 0, 'translated' => 0, 'untranslated' => 0];

        if (! File::isDirectory($sourcePath)) {
            return $totals;
        }

        $files = collect(File::allFiles($sourcePath))
            ->filter(fn ($file) => $file->getExtension() === 'php');

        foreach ($files as $file) {
            $sourceFile = $file->getPathname();
            $translationFile = $this->translationFileService->getTranslationPath($sourceFile);
            $translations = $this->translationFileService->loadTranslations($translationFile);

            $outputFile = null;
            if ($outputPath !== null) {
                $relativePath = substr($sourceFile, strlen($sourcePath));
                $outputFile = $outputPath.$relativePath;
            }

            if (empty($translations)) {
                if ($outputFile !== null && ! $dryRun) {
                    $this->ensureDirectory(dirname($outputFile));
                    File::copy($sourceFile, $outputFile);
                }
                $totals['files']++;

                continue;
            }

            $stats = $this->applyFile($sourceFile, $outputFile, $translations, $reverse, $dryRun);

            $totals['files']++;
            $totals['translated'] += $stats['translated'];
            $totals['untranslated'] += $stats['untranslated'];
        }

        return $totals;
    }

    // -----------------------------------------------------------------
    // Legacy alias kept for code paths still calling buildFile()
    // -----------------------------------------------------------------

    /**
     * @deprecated Use applyFile() instead. This shim preserves the old
     *   signature for existing callers in CommentBuildCommand.
     *
     * @param  array<string, string>  $translations
     * @return array{translated: int, untranslated: int}
     */
    public function buildFile(string $source, string $output, array $translations): array
    {
        $stats = $this->applyFile($source, $output, $translations);

        return [
            'translated' => $stats['translated'],
            'untranslated' => $stats['untranslated'],
        ];
    }
}
