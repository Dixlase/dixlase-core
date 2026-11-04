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

namespace App\Support\Content\Embed;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * Start an embed on a line that holds `@[service](URL)` and nothing else
 *
 * A line that names a service this renderer does not know, or that carries
 * anything besides the embed, stays an ordinary paragraph.
 */
class ContentEmbedStartParser implements BlockStartParserInterface
{
    private const PATTERN = '/^@\[([a-z]+)\]\(\s*(\S+?)\s*\)[ \t]*$/i';

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()
            || $parserState->getParagraphContent() !== null
            || ! $parserState->getActiveBlockParser()->isContainer()) {
            return BlockStart::none();
        }

        $cursor->advanceToNextNonSpaceOrTab();
        if ($cursor->getCurrentCharacter() !== '@'
            || ! preg_match(self::PATTERN, $cursor->getRemainder(), $matches)) {
            return BlockStart::none();
        }

        $service = strtolower($matches[1]);
        if (! in_array($service, ContentEmbedRenderer::SERVICES, true)) {
            return BlockStart::none();
        }

        $cursor->advanceToEnd();

        return BlockStart::of(new ContentEmbedParser($service, $matches[2]))->at($cursor);
    }
}
