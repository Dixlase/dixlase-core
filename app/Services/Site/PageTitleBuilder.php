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

namespace App\Services\Site;

use App\Contracts\Site\PageTitleBuilderInterface;
use App\Helpers\ConfigHelper;

/**
 * Default `<title>` composition.
 *
 * Site root (no page name)  : {site name}{sep}{tagline}   e.g. "Dixlase - Brand sites"
 * Sub page (page name given): {page name}{sep}{site name} e.g. "Contact - Dixlase"
 * Empty tagline             : {site name}                 e.g. "Dixlase"
 *
 * The page-specific part comes first on sub pages because search
 * results truncate the tail, so the distinguishing words have to lead.
 *
 * Both the separator and the two formats are configurable through
 * `config/dixlase.php`. Site name and tagline are read through
 * {@see ConfigHelper}, which returns the fallback instead of throwing
 * when the settings table is absent (installer, maintenance screens).
 *
 * Subclasses may override {@see siteName()} and {@see tagline()} to
 * supply localised values.
 *
 * @api Stable API available for use from plugins/themes
 */
class PageTitleBuilder implements PageTitleBuilderInterface
{
    /**
     * Characters an older view may have embedded in front of the page
     * name: ASCII hyphen, en/em dash and vertical bar.
     */
    private const LEADING_SEPARATOR_PATTERN = '/^[\s\x{00A0}]*(?:[-\x{2013}\x{2014}|][\s\x{00A0}]*)+/u';

    /**
     * {@inheritDoc}
     */
    public function build(?string $pageName = null): string
    {
        $siteName = $this->squeeze($this->siteName());
        $page = $this->normalizePageName($pageName);

        if ($page === '') {
            $tagline = $this->squeeze($this->tagline());

            if ($tagline === '' || $tagline === $siteName) {
                return $siteName;
            }

            return $this->compose(
                (string) config('dixlase.page_title.root_format', ':site:separator:tagline'),
                $siteName,
                $tagline,
                ''
            );
        }

        if ($siteName === '' || $siteName === $page) {
            return $page;
        }

        return $this->compose(
            (string) config('dixlase.page_title.page_format', ':page:separator:site'),
            $siteName,
            '',
            $page
        );
    }

    /**
     * {@inheritDoc}
     */
    public function separator(): string
    {
        $separator = config('dixlase.page_title.separator', ' - ');

        return is_string($separator) && $separator !== '' ? $separator : ' - ';
    }

    /**
     * The site name shown in the title.
     */
    protected function siteName(): string
    {
        return ConfigHelper::getAppName();
    }

    /**
     * The site tagline, or '' when the operator has not set one.
     */
    protected function tagline(): string
    {
        return ConfigHelper::getSiteTagline();
    }

    /**
     * Drop a separator a caller embedded in front of the page name and
     * flatten any run of whitespace, so no title can carry the double
     * space that hand-built concatenation used to produce.
     */
    protected function normalizePageName(?string $pageName): string
    {
        if ($pageName === null) {
            return '';
        }

        $value = preg_replace(self::LEADING_SEPARATOR_PATTERN, '', $pageName);

        return $this->squeeze(is_string($value) ? $value : $pageName);
    }

    /**
     * Fill a format string and collapse whatever the parts leave behind.
     */
    private function compose(string $format, string $siteName, string $tagline, string $pageName): string
    {
        $filled = strtr($format, [
            ':site' => $siteName,
            ':tagline' => $tagline,
            ':page' => $pageName,
            ':separator' => $this->separator(),
        ]);

        return $this->squeeze($filled);
    }

    /**
     * Trim the ends and reduce every inner whitespace run to one space.
     */
    private function squeeze(string $value): string
    {
        $collapsed = preg_replace('/[\s\x{00A0}]+/u', ' ', $value);

        return trim(is_string($collapsed) ? $collapsed : $value);
    }
}
