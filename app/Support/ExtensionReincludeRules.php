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

namespace App\Support;

/**
 * Finds and removes the `!plugins/<Name>/` and `!themes/<Name>/` rules that
 * older versions of core wrote into `.gitignore` and `.git/info/exclude`.
 *
 * Core ignores `plugins/*` and `themes/*` on purpose: every extension is its
 * own repository and core never tracks it. Each re-include rule undid that
 * for one directory, so `git status` in core listed the extension and
 * `git add -A` would have staged its files into core. Core no longer writes
 * these rules; this class is what `dls:sync-gitignore` and
 * `dls:sync-git-exclude` use to clean up the ones already there.
 *
 * Pure string work, no file system access, so it can be tested in isolation.
 */
final class ExtensionReincludeRules
{
    /**
     * Start and end markers of the auto-managed sections that older versions
     * wrote into `.git/info/exclude`, keyed by the rule type.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const SECTION_MARKERS = [
        'plugins' => ['# === Plugin exclusions (auto-managed) ===', '# === End plugins exclusions ==='],
        'themes' => ['# === Theme exclusions (auto-managed) ===', '# === End themes exclusions ==='],
    ];

    /**
     * Comment line written inside each auto-managed section.
     */
    private const SECTION_NOTE = '# Do not edit this section manually';

    /**
     * Whether a line is a re-include rule for an extension directory.
     *
     * @param  list<string>  $types  Rule types to match (`plugins`, `themes`)
     * @param  string|null  $name  Match only this directory name, or any when null
     */
    public static function isRule(string $line, array $types = ['plugins', 'themes'], ?string $name = null): bool
    {
        if (preg_match('#^!(plugins|themes)/([A-Za-z0-9_-][A-Za-z0-9._-]*)/?$#', trim($line), $m) !== 1) {
            return false;
        }

        if (! in_array($m[1], $types, true)) {
            return false;
        }

        return $name === null || $m[2] === $name;
    }

    /**
     * Remove matching rules from a file's content.
     *
     * An auto-managed section whose body holds nothing but matching rules,
     * its note and blank lines is removed whole, markers included, together
     * with the blank line written in front of it. A section that holds
     * anything else keeps its markers and the other lines.
     *
     * @param  list<string>  $types  Rule types to remove (`plugins`, `themes`)
     * @param  string|null  $name  Remove only this directory's rule, or every rule when null
     * @return array{content: string, removed: list<string>} The cleaned content and the removed lines
     */
    public static function strip(string $content, array $types = ['plugins', 'themes'], ?string $name = null): array
    {
        $lines = explode("\n", $content);
        $drop = [];

        foreach (self::SECTION_MARKERS as $type => [$start, $end]) {
            if (! in_array($type, $types, true)) {
                continue;
            }

            foreach (self::sections($lines, $start, $end) as [$from, $to]) {
                $whole = true;
                for ($i = $from + 1; $i < $to; $i++) {
                    $trimmed = trim($lines[$i]);
                    if ($trimmed === '' || $trimmed === self::SECTION_NOTE) {
                        continue;
                    }
                    if (! self::isRule($lines[$i], [$type], $name)) {
                        $whole = false;
                        break;
                    }
                }

                if (! $whole) {
                    continue;
                }

                for ($i = $from; $i <= $to; $i++) {
                    $drop[$i] = true;
                }
                if ($from > 0 && trim($lines[$from - 1]) === '') {
                    $drop[$from - 1] = true;
                }
            }
        }

        $kept = [];
        $removed = [];
        foreach ($lines as $i => $line) {
            if (isset($drop[$i]) || self::isRule($line, $types, $name)) {
                if (trim($line) !== '') {
                    $removed[] = $line;
                }

                continue;
            }
            $kept[] = $line;
        }

        return ['content' => implode("\n", $kept), 'removed' => $removed];
    }

    /**
     * Line ranges of every complete section between the two markers.
     *
     * @param  list<string>  $lines
     * @return list<array{0: int, 1: int}> Start and end line index of each section
     */
    private static function sections(array $lines, string $start, string $end): array
    {
        $ranges = [];
        $open = null;

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if ($trimmed === $start) {
                $open = $i;
            } elseif ($trimmed === $end && $open !== null) {
                $ranges[] = [$open, $i];
                $open = null;
            }
        }

        return $ranges;
    }
}
