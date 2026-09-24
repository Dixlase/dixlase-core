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

namespace Tests\Feature\View;

use App\Contracts\Site\PageTitleBuilderInterface;
use App\Services\Site\PageTitleBuilder;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The `@pageTitle` directive and the dls_page_title() helper.
 *
 * No settings rows are involved: ConfigHelper falls back to config()
 * whenever the settings table is unreachable, which is also what the
 * installer and the maintenance screens rely on.
 */
class PageTitleDirectiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.name' => 'Dixlase',
            'app.tagline' => '',
            'dixlase.page_title.separator' => ' - ',
            'dixlase.page_title.root_format' => ':site:separator:tagline',
            'dixlase.page_title.page_format' => ':page:separator:site',
        ]);
    }

    public function test_it_renders_the_site_name_when_no_title_section_is_set(): void
    {
        $this->assertSame('<title>Dixlase</title>', trim(Blade::render('@pageTitle')));
    }

    public function test_it_appends_the_tagline_on_the_site_root(): void
    {
        config(['app.tagline' => 'Brand sites']);

        $this->assertSame(
            '<title>Dixlase - Brand sites</title>',
            trim(Blade::render('@pageTitle'))
        );
    }

    public function test_it_reads_the_title_section(): void
    {
        $rendered = Blade::render("@section('title', 'Contact')\n@pageTitle");

        $this->assertSame('<title>Contact - Dixlase</title>', trim($rendered));
    }

    /**
     * The production symptom: a view that embedded the separator itself
     * used to render "Dixlase  - Home" with two spaces.
     */
    public function test_a_separator_embedded_in_the_section_produces_no_double_space(): void
    {
        $rendered = Blade::render("@section('title', ' - Home')\n@pageTitle");

        $this->assertSame('<title>Home - Dixlase</title>', trim($rendered));
        $this->assertStringNotContainsString('  ', $rendered);
    }

    public function test_an_explicit_argument_overrides_the_section(): void
    {
        $rendered = Blade::render(
            "@section('title', 'Ignored')\n@pageTitle(\$given)",
            ['given' => 'Chosen']
        );

        $this->assertSame('<title>Chosen - Dixlase</title>', trim($rendered));
    }

    public function test_the_title_is_escaped_exactly_once(): void
    {
        $fromSection = Blade::render("@section('title', \$given)\n@pageTitle", ['given' => 'Tips & tricks']);
        $fromArgument = Blade::render('@pageTitle($given)', ['given' => 'Tips & tricks']);

        $this->assertSame('<title>Tips &amp; tricks - Dixlase</title>', trim($fromSection));
        $this->assertSame('<title>Tips &amp; tricks - Dixlase</title>', trim($fromArgument));
    }

    public function test_markup_in_a_title_cannot_escape_the_element(): void
    {
        $rendered = Blade::render('@pageTitle($given)', ['given' => '</title><script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
    }

    public function test_the_helper_honours_a_rebound_contract(): void
    {
        $this->app->bind(PageTitleBuilderInterface::class, function () {
            return new class extends PageTitleBuilder
            {
                public function build(?string $pageName = null): string
                {
                    return 'Overridden';
                }
            };
        });

        $this->assertSame('Overridden', dls_page_title('Contact'));
        $this->assertSame('<title>Overridden</title>', trim(Blade::render('@pageTitle')));
    }
}
