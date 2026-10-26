<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards the shared contributor rules and the CLAUDE.md files generated from them.
 *
 * The rules are this repository's own — the comment language, the licence header,
 * the migration policy, the test-safety rules, the branch and release gate — and
 * CLAUDE.md says they live at `resources/ai/shared-rules-*.md`. For a long time
 * that path did not exist here: the rules were kept by the document generators,
 * outside core, and each generator kept its own copy. The copies drifted, and the
 * drift was found only by comparing them by hand (#772).
 *
 * So this test holds three things in place.
 *
 * 1. The documented path resolves. A contributor who follows CLAUDE.md finds the
 *    file it names.
 * 2. CLAUDE.md reproduces the rules byte for byte, inside its marked region. That
 *    is the direction drift actually travels: CLAUDE.md is the file people read
 *    and the file people hand-edit, while the rules file is the one a generator
 *    reads. When the two disagree, one of them has been edited without the other,
 *    and `dls:claude:setup` would silently revert whichever was edited by hand.
 * 3. The rules name no specific extension. They are generic rules, and this
 *    repository is public — a path naming an extension that is not publicly
 *    distributed must not be published here, and such paths are exactly what #772
 *    found in one of the copies.
 *
 * The region markers are written by the generator, so a CLAUDE.md without them is
 * a failure too: it means the file was produced some other way.
 */
class SharedRulesFilesTest extends TestCase
{
    private const REPO_ROOT = __DIR__.'/../..';

    /**
     * The rules file of each locale, and the CLAUDE.md generated from it.
     *
     * @var array<string, string>
     */
    private const PAIRS = [
        'resources/ai/shared-rules-en.md' => 'CLAUDE.md',
        'resources/ai/shared-rules-ja.md' => 'CLAUDE.ja.md',
    ];

    public function test_the_shared_rules_exist_where_claude_md_says_they_do(): void
    {
        $claude = $this->read('CLAUDE.md');

        $this->assertStringContainsString(
            'resources/ai/shared-rules-*.md',
            $claude,
            'CLAUDE.md should keep telling contributors where the shared rules live.'
        );

        foreach (array_keys(self::PAIRS) as $rules) {
            $this->assertFileExists(
                self::REPO_ROOT.'/'.$rules,
                $rules.' is the path CLAUDE.md names, so it has to exist.'
            );

            $this->assertGreaterThan(
                10000,
                strlen($this->read($rules)),
                $rules.' looks truncated — the shared rules are a long document.'
            );
        }
    }

    public function test_claude_md_reproduces_the_shared_rules_verbatim(): void
    {
        foreach (self::PAIRS as $rules => $document) {
            $region = $this->rulesRegion($document);

            $this->assertSame(
                trim($this->read($rules)),
                $region,
                $document.' does not match '.$rules.'. One of the two was edited without the other; '
                .'regenerate the document with `dls:claude:setup` rather than editing it by hand.'
            );
        }
    }

    public function test_the_shared_rules_name_no_specific_extension(): void
    {
        foreach (array_keys(self::PAIRS) as $rules) {
            $matches = [];
            preg_match_all('#plugins/Dixlase[A-Za-z0-9_]*#', $this->read($rules), $matches);

            $this->assertSame(
                [],
                array_values(array_unique($matches[0])),
                $rules.' names a specific extension. The shared rules are generic, this repository is '
                .'public, and some extensions are not publicly distributed — describe the capability or '
                .'the command instead of the extension that carries it.'
            );
        }
    }

    /**
     * Return the part of a generated CLAUDE.md that the rules file fills.
     */
    private function rulesRegion(string $document): string
    {
        $matches = [];

        $found = preg_match(
            '/<!-- DIXLASE_RULES_START -->(.*)<!-- DIXLASE_RULES_END -->/s',
            $this->read($document),
            $matches
        );

        $this->assertSame(
            1,
            $found,
            $document.' has no DIXLASE_RULES_START / DIXLASE_RULES_END region. The generator writes '
            .'those markers, so a document without them was not generated.'
        );

        return trim($matches[1]);
    }

    private function read(string $relative): string
    {
        $raw = file_get_contents(self::REPO_ROOT.'/'.$relative);

        $this->assertNotFalse($raw, $relative.' is missing or unreadable.');

        return $raw;
    }
}
