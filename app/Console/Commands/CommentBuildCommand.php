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

use Illuminate\Console\Command;
use Plugins\DixlaseCoreDevKit\App\Services\CommentTranslation\CommentBuilderService;
use Plugins\DixlaseCoreDevKit\App\Services\CommentTranslation\TranslationFileService;

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
        {--locale= : Target locale (default: from config core-dev.comment_translation.default_locale)}
        {--in-place : Rewrite source files directly instead of copying to --output}
        {--reverse : Apply locale → EN direction (revert a previous in-place conversion)}
        {--output= : Output destination when not --in-place (default: dist/{locale})}
        {--path=app : Source path to scan (relative to project root)}
        {--dry-run : Compute substitutions but do not write any file}';

    /** @var string */
    protected $description = 'Apply translated comments to PHP source (copy or in-place)';

    public function handle(
        CommentBuilderService $builder,
        TranslationFileService $fileService,
    ): int {
        $locale = $this->resolveLocale($fileService);
        $fileService->setLocale($locale);

        $sourcePath = base_path($this->option('path'));
        $inPlace = (bool) $this->option('in-place');
        $reverse = (bool) $this->option('reverse');
        $dryRun = (bool) $this->option('dry-run');

        $direction = $reverse ? "{$locale} → EN" : "EN → {$locale}";

        $this->components->info("Source path: {$sourcePath}");
        $this->components->info("Locale:      {$locale} ({$direction})");

        if ($inPlace) {
            $this->components->info('Mode:        in-place'.($dryRun ? ' [DRY RUN]' : ''));
            $stats = $builder->applyDirectoryInPlace($sourcePath, $reverse, $dryRun);
        } else {
            $outputPath = $this->resolveOutputPath($locale);
            $this->components->info("Output path: {$outputPath}".($dryRun ? ' [DRY RUN]' : ''));

            if ($dryRun) {
                // For copy mode, dry-run just walks without writing. We
                // reuse the in-place walker semantics (no file I/O for
                // unchanged files) but report against the would-be output.
                $stats = $builder->applyDirectoryInPlace($sourcePath, $reverse, dryRun: true);
            } else {
                $stats = $builder->buildDirectory($sourcePath, $outputPath);
            }
        }

        return $this->renderResult($stats);
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
