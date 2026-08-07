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

namespace App\Console\Commands;

use App\Services\CommentTranslation\CommentBuilderService;
use App\Services\CommentTranslation\ExtensionDictionaryLocator;
use App\Services\CommentTranslation\TranslationFileService;
use Illuminate\Console\Command;

/**
 * Apply per-locale comment translations to PHP source.
 *
 * Two output modes:
 *   - copy mode (default): write the translated source to `--output=...`,
 *     leaving the canonical English source untouched. Used for release
 *     packaging.
 *   - in-place mode (`--in-place`): rewrite the source files directly.
 *     Used by `convert-comments.sh` to flip a development checkout
 *     between English and the requested locale.
 *
 * Two directions:
 *   - forward (default): EN → locale (apply the locale archive in place
 *     of the canonical EN comments).
 *   - reverse (`--reverse`): locale → EN (revert a previous in-place
 *     conversion).
 *
 * Substitution is AST-aware (`CommentBuilderService::applyToContent`):
 * only PHP comment tokens are rewritten, so user-facing string literals
 * that happen to match a dictionary key are NOT touched.
 *
 * Note: the underlying services currently live in DixlaseCoreDevKit; the
 * plugin must be installed and enabled. The dictionary itself ships with
 * Core under `resources/comment-translations/{locale}/`.
 */
class CommentBuildCommand extends Command
{
    /** @var string */
    protected $signature = 'dls:comment:build
        {--locale= : Target locale (default: from config comment-translation.default_locale)}
        {--in-place : Rewrite source files directly instead of copying to --output}
        {--reverse : Apply locale → EN direction (revert a previous in-place conversion)}
        {--output= : Output destination when not --in-place (default: dist/{locale})}
        {--path=app,resources/views : Comma-separated source paths to scan within each extension (relative to extension root)}
        {--include-plugins : Also walk plugins/*/resources/comment-translations/{locale}/}
        {--include-themes : Also walk themes/*/resources/comment-translations/{locale}/}
        {--dry-run : Compute substitutions but do not write any file}';

    /** @var string */
    protected $description = 'Apply translated comments to PHP source (copy or in-place)';

    public function handle(
        CommentBuilderService $builder,
        TranslationFileService $fileService,
        ExtensionDictionaryLocator $locator,
    ): int {
        $locale = $this->resolveLocale($fileService);
        $fileService->setLocale($locale);

        $rawPaths = (string) $this->option('path');
        $scanPaths = array_values(array_filter(
            array_map('trim', explode(',', $rawPaths)),
            fn ($p) => $p !== '',
        ));
        $inPlace = (bool) $this->option('in-place');
        $reverse = (bool) $this->option('reverse');
        $dryRun = (bool) $this->option('dry-run');
        $includePlugins = (bool) $this->option('include-plugins');
        $includeThemes = (bool) $this->option('include-themes');

        $direction = $reverse ? "{$locale} → EN" : "EN → {$locale}";
        $modeLabel = $inPlace ? 'in-place' : 'copy';
        if ($dryRun) {
            $modeLabel .= ' [DRY RUN]';
        }

        $entries = $locator->locate($locale, $includePlugins, $includeThemes);
        if (empty($entries)) {
            $this->components->error(
                "No dictionary directories found for locale '{$locale}'. ".
                'Did you mean a different --locale?'
            );

            return self::FAILURE;
        }

        $this->components->info("Locale:      {$locale} ({$direction})");
        $this->components->info('Scan paths:  '.implode(', ', $scanPaths).' (within each extension)');
        $this->components->info('Mode:        '.$modeLabel);
        $this->components->info('Extensions:  '.count($entries).' ('
            .implode(', ', array_map(fn ($e) => $e['kind'].'/'.$e['name'], $entries)).')');

        $totals = ['files' => 0, 'translated' => 0, 'untranslated' => 0];

        foreach ($entries as $entry) {
            $sourceRoot = $entry['root'];
            $dictRoot = $entry['dictRoot'];
            $outputRoot = $inPlace
                ? null
                : $this->resolveExtensionOutputRoot($entry, $locale);

            $entryStats = ['files' => 0, 'translated' => 0, 'untranslated' => 0];
            foreach ($scanPaths as $scanPath) {
                $stats = $builder->applyExtension(
                    $sourceRoot,
                    $dictRoot,
                    $scanPath,
                    $outputRoot,
                    $reverse,
                    $dryRun,
                );
                $entryStats['files'] += $stats['files'];
                $entryStats['translated'] += $stats['translated'];
                $entryStats['untranslated'] += $stats['untranslated'];
            }

            $totals['files'] += $entryStats['files'];
            $totals['translated'] += $entryStats['translated'];
            $totals['untranslated'] += $entryStats['untranslated'];

            if ($entryStats['files'] > 0) {
                $this->line(sprintf(
                    '  %-25s files=%-4d subs=%-5d pending=%d',
                    $entry['kind'].'/'.$entry['name'],
                    $entryStats['files'],
                    $entryStats['translated'],
                    $entryStats['untranslated'],
                ));
            }
        }

        return $this->renderResult($totals);
    }

    /**
     * Resolve the per-extension output root for copy mode.
     *
     * @param  array{root: string, dictRoot: string, kind: string, name: string}  $entry
     */
    protected function resolveExtensionOutputRoot(array $entry, string $locale): string
    {
        $option = $this->option('output');

        if (is_string($option) && $option !== '') {
            // Single user-supplied root; only valid for a single-extension
            // build (e.g. core only). For multi-extension we still nest by
            // kind/name to keep outputs distinct.
            $root = base_path($option);
            if ($entry['kind'] === 'core') {
                return $root;
            }

            return $root.'/'.$entry['kind'].'s/'.$entry['name'];
        }

        $base = base_path('dist/'.$locale);
        if ($entry['kind'] === 'core') {
            return $base;
        }

        return $base.'/'.$entry['kind'].'s/'.$entry['name'];
    }

    /**
     * Resolve the locale from the --locale option or config default.
     */
    protected function resolveLocale(TranslationFileService $fileService): string
    {
        $option = $this->option('locale');
        if (is_string($option) && $option !== '') {
            return $option;
        }

        return $fileService->getLocale();
    }

    /**
     * Resolve the output path for copy mode.
     */
    protected function resolveOutputPath(string $locale): string
    {
        $option = $this->option('output');
        if (is_string($option) && $option !== '') {
            return base_path($option);
        }

        return base_path('dist/'.$locale);
    }

    /**
     * @param  array{files: int, translated: int, untranslated: int}  $stats
     */
    protected function renderResult(array $stats): int
    {
        if ($stats['files'] === 0) {
            $this->components->warn('No PHP files found to process.');

            return self::SUCCESS;
        }

        $total = $stats['translated'] + $stats['untranslated'];
        $rate = $total > 0
            ? round($stats['translated'] / $total * 100, 1)
            : 0;

        $this->newLine();
        $this->components->twoColumnDetail('Files processed', (string) $stats['files']);
        $this->components->twoColumnDetail('Substitutions applied', (string) $stats['translated']);
        $this->components->twoColumnDetail('Pending entries skipped', (string) $stats['untranslated']);
        $this->components->twoColumnDetail('Coverage', "{$rate}%");

        $this->newLine();
        $this->components->info('Build complete.');

        return self::SUCCESS;
    }
}
