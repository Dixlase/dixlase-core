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

namespace Tests\Unit\Services\Site;

use App\Services\Site\PageTitleBuilder;
use Tests\TestCase;

/**
 * Composition rules of the default page title builder.
 *
 * The site name and tagline are supplied by a subclass instead of the
 * settings table, so the rules are asserted without a database.
 */
class PageTitleBuilderTest extends TestCase
{
    private function builder(string $siteName = 'Dixlase', string $tagline = ''): PageTitleBuilder
    {
        return new class($siteName, $tagline) extends PageTitleBuilder
        {
            public function __construct(private string $site, private string $line) {}

            protected function siteName(): string
            {
                return $this->site;
            }

            protected function tagline(): string
            {
                return $this->line;
            }
        };
    }

    public function test_root_pairs_the_site_name_with_the_tagline(): void
    {
        $this->assertSame(
            'Dixlase - Brand sites',
            $this->builder('Dixlase', 'Brand sites')->build()
        );
    }

    public function test_root_without_a_tagline_is_the_site_name_alone(): void
    {
        $this->assertSame('Dixlase', $this->builder('Dixlase', '')->build());
        $this->assertSame('Dixlase', $this->builder('Dixlase', '   ')->build());
    }

    public function test_root_accepts_an_empty_page_name_as_the_root(): void
    {
        $builder = $this->builder('Dixlase', 'Brand sites');

        $this->assertSame('Dixlase - Brand sites', $builder->build(''));
        $this->assertSame('Dixlase - Brand sites', $builder->build('   '));
        $this->assertSame('Dixlase - Brand sites', $builder->build(null));
    }

    public function test_sub_page_leads_with_the_page_name(): void
    {
        $this->assertSame(
            'Contact - Dixlase',
            $this->builder('Dixlase', 'Brand sites')->build('Contact')
        );
    }

    /**
     * The bug this API replaces: a view that embedded the separator itself
     * produced "Dixlase  - Home" once the theme added its own space.
     */
    public function test_a_separator_embedded_by_the_caller_is_dropped(): void
    {
        $builder = $this->builder('Dixlase');

        foreach ([' - Home', '- Home', ' -Home', ' | Home', ' — Home', ' – Home', ' - - Home'] as $given) {
            $this->assertSame('Home - Dixlase', $builder->build($given), $given);
        }
    }

    public function test_inner_whitespace_runs_collapse_to_one_space(): void
    {
        $this->assertSame(
            'Our team - Dixlase',
            $this->builder('Dixlase')->build("Our \n  team ")
        );
        $this->assertSame(
            'Dixlase - Brand sites',
            $this->builder('Dixlase', 'Brand  sites')->build()
        );
    }

    public function test_no_title_ever_repeats_the_name(): void
    {
        $this->assertSame('Dixlase', $this->builder('Dixlase', 'Dixlase')->build());
        $this->assertSame('Dixlase', $this->builder('Dixlase')->build('Dixlase'));
    }

    public function test_a_missing_site_name_leaves_the_page_name_standing(): void
    {
        $this->assertSame('Contact', $this->builder('', 'Brand sites')->build('Contact'));
        $this->assertSame('', $this->builder('', '')->build());
    }

    public function test_the_separator_and_the_formats_come_from_config(): void
    {
        config([
            'dixlase.page_title.separator' => ' | ',
            'dixlase.page_title.root_format' => ':tagline:separator:site',
            'dixlase.page_title.page_format' => ':site:separator:page',
        ]);

        $builder = $this->builder('Dixlase', 'Brand sites');

        $this->assertSame('Brand sites | Dixlase', $builder->build());
        $this->assertSame('Dixlase | Contact', $builder->build('Contact'));
        $this->assertSame(' | ', $builder->separator());
    }

    public function test_an_unusable_separator_falls_back_to_the_default(): void
    {
        config(['dixlase.page_title.separator' => '']);

        $this->assertSame(' - ', $this->builder()->separator());
        $this->assertSame('Contact - Dixlase', $this->builder('Dixlase')->build('Contact'));
    }
}
