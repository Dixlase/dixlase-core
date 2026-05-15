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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
use Symfony\Component\Finder\Finder;

/**
 * Inventory scanner for Alpine.js CSP-build readiness.
 *
 * Walks Blade views in core/plugins/themes and classifies every Alpine
 * usage that would need attention before switching the bundle import to
 *
 * @alpinejs/csp. Output is a table summary plus optional JSON ledger.
 *
 * The CSP build of Alpine forbids:
 *   - inline x-data="{...}" object literals (must use Alpine.data() registration)
 *   - inline x-init="..." with logic (must be init() method)
 *   - object-literal expressions in directives (e.g. :class="{ active: open }")
 *   - arrow functions, template strings, complex expressions
 *
 * This scanner is heuristic (regex-based) and intended as a migration
 * ledger, not a perfect parser. Reported items are candidates that should
 * be reviewed by hand before bundle switch.
 *
 * Known limitations:
 *   - Single-line attributes only. Multi-line x-data="{ ... \n ... }" blocks
 *     are undercounted. Run `grep -c 'x-data=' resources/views/...` for a
 *     ground-truth count and compare.
 *   - Blade component prop bindings (`<x-foo :bar="$baz">`) are filtered
 *     heuristically (looksLikePhpExpression). Edge cases may slip through.
 */
class CspScanAlpine extends Command
{
    protected $signature = 'dls:csp:scan-alpine
        {--json= : Path to write JSON ledger output}
        {--fail-on=0 : Exit with non-zero when total count exceeds this number (for CI gates)}
        {--scope=all : Scan scope: all, core, plugins, themes}';

    protected $description = 'Inventory Alpine.js usages that need migration before switching to @alpinejs/csp';

    /**
     * Directives whose expression body is interpreted by Alpine.
     * Object-literal expressions in any of these are CSP-build-incompatible.
     */
    private const DIRECTIVE_ATTRS = [
        'x-show', 'x-text', 'x-html', 'x-effect',
        'x-bind', 'x-on',
    ];

    /**
     * Event shorthand prefixes scanned: @click, @input, @submit, ...
     * Plus :class, :style, etc. (x-bind shorthand).
     */
    private const SHORTHAND_PREFIXES = ['@', ':'];

    public function handle(): int
    {
        $scope = $this->option('scope');
        $roots = $this->resolveRoots($scope);

        if (empty($roots)) {
            $this->error("No scan roots resolved for scope '{$scope}'.");

            return self::FAILURE;
        }

        $findings = [];
        foreach ($roots as $label => $path) {
            if (! is_dir($path)) {
                continue;
            }

            $finder = Finder::create()
                ->files()
                ->in($path)
                ->name('*.blade.php');

            foreach ($finder as $file) {
                $relPath = str_replace(base_path().'/', '', $file->getPathname());
                $contents = $file->getContents();
                $findings = array_merge(
                    $findings,
                    $this->scanFile($relPath, $contents, $label)
                );
            }
        }

        $this->renderSummary($findings);
        $this->renderTable($findings);

        if ($jsonPath = $this->option('json')) {
            file_put_contents(
                base_path($jsonPath),
                json_encode([
                    'generated_at' => now()->toIso8601String(),
                    'scope' => $scope,
                    'total' => count($findings),
                    'findings' => $findings,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->info("JSON ledger written to: {$jsonPath}");
        }

        $failOn = (int) $this->option('fail-on');
        if ($failOn > 0 && count($findings) > $failOn) {
            $this->error(sprintf(
                'Findings (%d) exceed --fail-on threshold (%d).',
                count($findings),
                $failOn
            ));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> map of label => absolute path
     */
    private function resolveRoots(string $scope): array
    {
        $coreViews = base_path('resources/views');
        $pluginsRoot = base_path('plugins');
        $themesRoot = base_path('themes');

        $roots = [];

        if (in_array($scope, ['all', 'core'], true)) {
            $roots['core'] = $coreViews;
        }
        if (in_array($scope, ['all', 'plugins'], true) && is_dir($pluginsRoot)) {
            foreach (glob($pluginsRoot.'/*/resources/views', GLOB_ONLYDIR) as $dir) {
                $name = basename(dirname(dirname($dir)));
                $roots["plugin:{$name}"] = $dir;
            }
        }
        if (in_array($scope, ['all', 'themes'], true) && is_dir($themesRoot)) {
            foreach (glob($themesRoot.'/*/resources', GLOB_ONLYDIR) as $dir) {
                $name = basename(dirname($dir));
                $roots["theme:{$name}"] = $dir;
            }
        }

        return $roots;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scanFile(string $relPath, string $contents, string $sourceLabel): array
    {
        $findings = [];
        $lines = explode("\n", $contents);

        foreach ($lines as $i => $line) {
            $lineNo = $i + 1;

            // x-data="..."
            if (preg_match_all('/x-data\s*=\s*"([^"]*)"/', $line, $m)) {
                foreach ($m[1] as $expr) {
                    $findings[] = [
                        'source' => $sourceLabel,
                        'file' => $relPath,
                        'line' => $lineNo,
                        'kind' => 'x-data',
                        'classification' => $this->classifyXData($expr),
                        'excerpt' => $this->trimExcerpt($expr),
                    ];
                }
            }

            // x-init="..."
            if (preg_match_all('/x-init\s*=\s*"([^"]*)"/', $line, $m)) {
                foreach ($m[1] as $expr) {
                    $findings[] = [
                        'source' => $sourceLabel,
                        'file' => $relPath,
                        'line' => $lineNo,
                        'kind' => 'x-init',
                        'classification' => $this->classifyExpression($expr),
                        'excerpt' => $this->trimExcerpt($expr),
                    ];
                }
            }

            // Skip Blade component prop bindings — `<x-foo :bar="$baz">` looks
            // like an Alpine `:bar` binding to regex but is actually PHP
            // injected at compile time.
            $isBladeComponentLine = (bool) preg_match('/<\s*x-[a-z0-9._:-]+/i', $line);

            // Other directives + shorthand event/bind handlers
            $patterns = [
                // x-show, x-text, x-html, x-effect (full names)
                '/(x-(?:show|text|html|effect))\s*=\s*"([^"]*)"/',
                // x-bind:foo / x-on:foo
                '/(x-(?:bind|on):[a-zA-Z0-9_.\-]+)\s*=\s*"([^"]*)"/',
                // shorthand :foo and @foo. Negative lookbehind prevents
                // double-matching the ":click" inside "x-on:click" etc.
                '/(?<![a-zA-Z0-9_-])([@:][a-zA-Z0-9_.\-]+)\s*=\s*"([^"]*)"/',
            ];
            foreach ($patterns as $pat) {
                if (preg_match_all($pat, $line, $m, PREG_SET_ORDER)) {
                    foreach ($m as $match) {
                        [$_, $attr, $expr] = $match;

                        // Filter out PHP/Blade expressions wrongly captured by
                        // the `:foo` shorthand pattern. Alpine expressions
                        // never use PHP-only constructs.
                        if ($isBladeComponentLine && str_starts_with($attr, ':') && $this->looksLikePhpExpression($expr)) {
                            continue;
                        }
                        if (str_starts_with($attr, ':') && $this->looksLikePhpExpression($expr)) {
                            continue;
                        }

                        $cls = $this->classifyDirectiveExpression($expr);
                        if ($cls === 'trivial') {
                            // Skip — clean for CSP build.
                            continue;
                        }
                        $findings[] = [
                            'source' => $sourceLabel,
                            'file' => $relPath,
                            'line' => $lineNo,
                            'kind' => "directive:{$attr}",
                            'classification' => $cls,
                            'excerpt' => $this->trimExcerpt($expr),
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    /**
     * Classify x-data expression:
     * - "registered" = name reference like adminLayout() or accordion
     * - "trivial-literal" = simple object literal with primitives
     * - "complex-literal" = literal with logic / nested objects / methods
     */
    private function classifyXData(string $expr): string
    {
        $trim = trim($expr);

        // Empty or pure name reference: foo or foo()
        if ($trim === '' || preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*(\([^)]*\))?$/', $trim)) {
            return 'registered';
        }

        // Object literal heuristic: starts with {
        if (str_starts_with($trim, '{')) {
            // Trivial = no methods, no nested objects, no arrow fns
            $hasMethod = preg_match('/[a-zA-Z_$][a-zA-Z0-9_$]*\s*\([^)]*\)\s*\{/', $trim);
            $hasArrow = str_contains($trim, '=>');
            $hasTemplate = str_contains($trim, '`');
            $hasNested = substr_count($trim, '{') > 1;

            if ($hasMethod || $hasArrow || $hasTemplate || $hasNested) {
                return 'complex-literal';
            }

            return 'trivial-literal';
        }

        return 'complex-literal';
    }

    /**
     * Classify a generic Alpine expression (for x-init, etc.):
     * - "trivial" = property access / method call / boolean
     * - "needs-rewrite" = arrow fns, template strings, complex chains
     */
    private function classifyExpression(string $expr): string
    {
        $trim = trim($expr);

        if ($trim === '') {
            return 'trivial';
        }

        if (str_contains($trim, '=>')
            || str_contains($trim, '`')
            || preg_match('/\bfunction\s*\(/', $trim)
        ) {
            return 'needs-rewrite';
        }

        return 'trivial';
    }

    /**
     * Classify directive expression — :class, @click, x-show etc.
     * Object literals are CSP-build-hostile in any directive.
     */
    private function classifyDirectiveExpression(string $expr): string
    {
        $trim = trim($expr);

        if ($trim === '') {
            return 'trivial';
        }

        // Object literal -> needs rewrite (heaviest hitter for :class)
        if (preg_match('/^\s*\{[^}]*[:][^}]*\}\s*$/', $trim)
            || preg_match('/[\$\w]\.\w+\s*\(\s*\{/', $trim)
            || preg_match('/[(,]\s*\{[^}]*[:]/', $trim)
        ) {
            return 'object-literal';
        }

        if (str_contains($trim, '=>')
            || str_contains($trim, '`')
            || preg_match('/\bfunction\s*\(/', $trim)
        ) {
            return 'needs-rewrite';
        }

        return 'trivial';
    }

    /**
     * Heuristic: does this look like a PHP expression embedded in Blade?
     *
     * Catches:
     *   - $variable, $obj->prop, $arr['key']
     *   - __('...'), route('...'), old(...), config(...), auth()
     *   - Concatenation with .
     *   - Ternary with ?: (Alpine uses === comparisons more than ternaries)
     *
     * Does NOT match Alpine magics like $el, $refs, $store, $watch, $dispatch.
     */
    private function looksLikePhpExpression(string $expr): bool
    {
        $trim = trim($expr);

        if ($trim === '') {
            return false;
        }

        // Alpine magic vars at start = NOT PHP
        if (preg_match('/^\$(el|refs|store|watch|dispatch|nextTick|root|id|data|bind)\b/', $trim)) {
            return false;
        }

        // PHP variable / arrow operator
        if (preg_match('/\$[a-zA-Z_]\w*/', $trim) && ! preg_match('/^\s*\$(el|refs|store|watch|dispatch|nextTick|root|id|data|bind)\b/', $trim)) {
            return true;
        }

        // PHP helper functions common in Blade
        if (preg_match('/\b(__|route|old|config|auth|trans|csrf_field|csrf_token|asset|url|session|request|view|env)\s*\(/', $trim)) {
            return true;
        }

        // Quoted string literal as the entire value: typically a Blade prop
        if (preg_match('/^[\'"][^\'"]*[\'"]$/', $trim)) {
            return true;
        }

        return false;
    }

    private function trimExcerpt(string $expr): string
    {
        $expr = preg_replace('/\s+/', ' ', trim($expr));

        return strlen($expr) > 80 ? substr($expr, 0, 77).'...' : $expr;
    }

    /**
     * @param  array<int, array<string, mixed>>  $findings
     */
    private function renderSummary(array $findings): void
    {
        $byKind = [];
        $byClassification = [];
        $bySource = [];

        foreach ($findings as $f) {
            $byKind[$f['kind']] = ($byKind[$f['kind']] ?? 0) + 1;
            $byClassification[$f['classification']] = ($byClassification[$f['classification']] ?? 0) + 1;
            $bySource[$f['source']] = ($bySource[$f['source']] ?? 0) + 1;
        }

        $this->info('=== Alpine CSP Migration Inventory ===');
        $this->line('Total findings: '.count($findings));
        $this->newLine();

        if (! empty($byClassification)) {
            $this->line('By classification:');
            ksort($byClassification);
            foreach ($byClassification as $k => $n) {
                $this->line(sprintf('  %-20s %5d', $k, $n));
            }
            $this->newLine();
        }

        if (! empty($byKind)) {
            $this->line('By kind:');
            ksort($byKind);
            foreach ($byKind as $k => $n) {
                $this->line(sprintf('  %-30s %5d', $k, $n));
            }
            $this->newLine();
        }

        if (! empty($bySource)) {
            $this->line('By source:');
            ksort($bySource);
            foreach ($bySource as $k => $n) {
                $this->line(sprintf('  %-30s %5d', $k, $n));
            }
            $this->newLine();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $findings
     */
    private function renderTable(array $findings): void
    {
        if (empty($findings)) {
            $this->info('No migration findings. Codebase looks ready for @alpinejs/csp.');

            return;
        }

        // Only render full table for verbose output to avoid drowning the terminal.
        if (! $this->getOutput()->isVerbose()) {
            $this->line('(Run with -v for the full per-finding table, or use --json=path/to/file.json.)');

            return;
        }

        $this->table(
            ['Source', 'File:Line', 'Kind', 'Class', 'Excerpt'],
            array_map(fn ($f) => [
                $f['source'],
                $f['file'].':'.$f['line'],
                $f['kind'],
                $f['classification'],
                $f['excerpt'],
            ], $findings)
        );
    }
}
