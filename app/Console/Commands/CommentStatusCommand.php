<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Console\Commands;

use App\Services\CommentTranslation\ExtensionDictionaryLocator;
use Illuminate\Console\Command;
use Plugins\DixlaseCoreDevKit\App\Services\CommentTranslation\TranslationFileService;

/**
 * Display comment-translation progress for a locale.
 *
 * Without arguments, shows per-directory totals and review-status counts.
 * With `--filter=<status>`, lists individual entries matching the filter
 * so they can be batch-reviewed.
 *
 * `--strict` makes the command exit with status 1 if any pending
 * (untranslated) entries exist — used in release CI to block publishing
 * a tag with translation gaps.
 */
class CommentStatusCommand extends Command
{
    /** @var string */
    protected $signature = 'dls:comment:status
        {--locale= : Locale to inspect (default: from config core-dev.comment_translation.default_locale)}
        {--strict : Exit 1 if any pending entries exist (use in release CI)}
        {--include-plugins : Also aggregate plugins/*/resources/comment-translations/{locale}/}
        {--include-themes : Also aggregate themes/*/resources/comment-translations/{locale}/}
        {--filter= : Filter entries: untranslated, machine, reviewed, human, or unreviewed}
        {--limit=50 : Max entries to display when filtering}';

    /** @var string */
    protected $description = 'Display comment-translation progress; --filter= lists entries by review status';

    public function handle(
        TranslationFileService $fileService,
        ExtensionDictionaryLocator $locator,
    ): int {
        $locale = $this->option('locale');
        if (is_string($locale) && $locale !== '') {
            $fileService->setLocale($locale);
        }

        $includePlugins = (bool) $this->option('include-plugins');
        $includeThemes = (bool) $this->option('include-themes');

        // Aggregate dictionary file paths from every requested extension.
        // Each entry tracks its dictRoot so we can render dirs relative
        // to the right anchor when summarising.
        $entries = $locator->locate($fileService->getLocale(), $includePlugins, $includeThemes);
        $extFiles = [];
        foreach ($entries as $entry) {
            $files = $fileService->getAllTranslationFilesIn($entry['dictRoot']);
            foreach ($files as $file) {
                $extFiles[] = ['entry' => $entry, 'file' => $file];
            }
        }

        if (empty($extFiles)) {
            $this->components->warn(
                "No translation files found for locale '{$fileService->getLocale()}'. ".
                'Run dls:comment:extract first to populate the dictionary.'
            );

            return $this->option('strict') ? self::FAILURE : self::SUCCESS;
        }

        $filter = $this->normalizeFilter($this->option('filter'));

        if ($filter !== null) {
            return $this->renderFilteredEntries($fileService, $extFiles, $filter, (int) $this->option('limit'));
        }

        return $this->renderSummary($fileService, $extFiles);
    }

    private function normalizeFilter(?string $option): ?string
    {
        if ($option === null || $option === '') {
            return null;
        }

        $normalized = strtolower($option);
        $allowed = array_merge(TranslationFileService::REVIEW_STATUSES, ['unreviewed']);

        if (! in_array($normalized, $allowed, true)) {
            $this->components->error(
                "Unknown filter '{$option}'. Allowed: ".implode(', ', $allowed)
            );

            return null;
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{entry: array{root: string, dictRoot: string, kind: string, name: string}, file: string}>  $extFiles
     */
    private function renderSummary(TranslationFileService $fileService, array $extFiles): int
    {
        /** @var array<string, array{translated: int, untranslated: int, total: int}> $dirStats */
        $dirStats = [];
        $grandTotal = 0;
        $grandTranslated = 0;

        $reviewCounts = [
            'untranslated' => 0,
            'machine' => 0,
            'reviewed' => 0,
            'human' => 0,
            'unmarked' => 0,
        ];

        foreach ($extFiles as $extFile) {
            $entry = $extFile['entry'];
            $file = $extFile['file'];
            $translations = $fileService->loadTranslations($file);
            $reviewStatus = $fileService->loadReviewStatus($file);

            if (empty($translations)) {
                continue;
            }

            $relativePath = ltrim(substr($file, strlen($entry['dictRoot'])), '/');
            $dirName = dirname($relativePath);
            $dirLabel = $entry['kind'] === 'core'
                ? $dirName
                : "{$entry['kind']}/{$entry['name']}: {$dirName}";

            if (! isset($dirStats[$dirLabel])) {
                $dirStats[$dirLabel] = ['translated' => 0, 'untranslated' => 0, 'total' => 0];
            }

            foreach ($translations as $key => $value) {
                $dirStats[$dirLabel]['total']++;
                $grandTotal++;

                $isTranslated = $value !== '';
                if ($isTranslated) {
                    $dirStats[$dirLabel]['translated']++;
                    $grandTranslated++;
                } else {
                    $dirStats[$dirLabel]['untranslated']++;
                }

                $status = $reviewStatus[$key] ?? null;
                if ($status === null) {
                    $reviewCounts[$isTranslated ? 'unmarked' : 'untranslated']++;
                } elseif (isset($reviewCounts[$status])) {
                    $reviewCounts[$status]++;
                } else {
                    $reviewCounts['unmarked']++;
                }
            }
        }

        ksort($dirStats);
        $rows = [];

        foreach ($dirStats as $dir => $stats) {
            $rate = $stats['total'] > 0
                ? round($stats['translated'] / $stats['total'] * 100, 1)
                : 0;

            $rows[] = [
                $dir,
                $stats['total'],
                $stats['translated'],
                $stats['untranslated'],
                "{$rate}%",
            ];
        }

        $this->components->info("Locale: {$fileService->getLocale()}");
        $this->table(
            ['Directory', 'Total', 'Translated', 'Pending', 'Coverage'],
            $rows
        );

        $grandRate = $grandTotal > 0
            ? round($grandTranslated / $grandTotal * 100, 1)
            : 0;
        $pendingTotal = $grandTotal - $grandTranslated;

        $this->newLine();
        $this->components->twoColumnDetail('Total entries', (string) $grandTotal);
        $this->components->twoColumnDetail('Translated', (string) $grandTranslated);
        $this->components->twoColumnDetail('Pending', (string) $pendingTotal);
        $this->components->twoColumnDetail('Overall coverage', "{$grandRate}%");

        $this->newLine();
        $this->components->info('Review status breakdown:');
        $this->components->twoColumnDetail('  untranslated', (string) $reviewCounts['untranslated']);
        $this->components->twoColumnDetail('  machine (auto-translated, awaiting review)', (string) $reviewCounts['machine']);
        $this->components->twoColumnDetail('  reviewed (human-confirmed)', (string) $reviewCounts['reviewed']);
        $this->components->twoColumnDetail('  human (human-written EN)', (string) $reviewCounts['human']);
        $this->components->twoColumnDetail('  unmarked (translated but no status set)', (string) $reviewCounts['unmarked']);

        if ($this->option('strict') && $pendingTotal > 0) {
            $this->newLine();
            $this->components->error(
                "STRICT mode: {$pendingTotal} pending entries — failing build."
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{entry: array{root: string, dictRoot: string, kind: string, name: string}, file: string}>  $extFiles
     */
    private function renderFilteredEntries(
        TranslationFileService $fileService,
        array $extFiles,
        string $filter,
        int $limit
    ): int {
        $shown = 0;
        $totalMatched = 0;

        foreach ($extFiles as $extFile) {
            $entry = $extFile['entry'];
            $file = $extFile['file'];
            $translations = $fileService->loadTranslations($file);
            $reviewStatus = $fileService->loadReviewStatus($file);

            if (empty($translations)) {
                continue;
            }

            $relativeFromDict = ltrim(substr($file, strlen($entry['dictRoot'])), '/');
            $relativePath = $entry['kind'] === 'core'
                ? $relativeFromDict
                : "{$entry['kind']}/{$entry['name']}/{$relativeFromDict}";
            $matches = [];

            foreach ($translations as $key => $value) {
                $status = $reviewStatus[$key] ?? null;
                $isTranslated = $value !== '';

                if (! $this->entryMatchesFilter($filter, $status, $isTranslated)) {
                    continue;
                }

                $matches[] = [$key, $value, $status ?? '(unmarked)'];
            }

            if ($matches === []) {
                continue;
            }

            $totalMatched += count($matches);

            if ($shown >= $limit) {
                continue;
            }

            $this->components->info($relativePath.' ('.count($matches).' matching)');
            foreach ($matches as [$key, $value, $status]) {
                if ($shown >= $limit) {
                    break;
                }
                $isTranslated = $value !== '';
                if ($isTranslated) {
                    $en = mb_strimwidth($key, 0, 60, '…');
                    $jp = mb_strimwidth($value, 0, 60, '…');
                    $this->line("  [{$status}] EN: {$en}");
                    $this->line("    locale: {$jp}");
                } else {
                    $jp = mb_strimwidth($key, 0, 60, '…');
                    $this->line("  [{$status}] pending: {$jp}");
                }
                $shown++;
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail("Filter: {$filter}", "{$totalMatched} entries matched");
        if ($totalMatched > $shown) {
            $remaining = $totalMatched - $shown;
            $this->components->twoColumnDetail('Truncated', "{$remaining} entries hidden (raise --limit to see more)");
        }

        if ($this->option('strict') && $filter === 'untranslated' && $totalMatched > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function entryMatchesFilter(string $filter, ?string $status, bool $isTranslated): bool
    {
        return match ($filter) {
            'untranslated' => ! $isTranslated,
            'machine' => $status === 'machine',
            'reviewed' => $status === 'reviewed',
            'human' => $status === 'human',
            'unreviewed' => $isTranslated && ($status === 'machine' || $status === null),
            default => false,
        };
    }
}
