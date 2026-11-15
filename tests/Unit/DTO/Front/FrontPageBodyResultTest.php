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

namespace Tests\Unit\DTO\Front;

use App\DTO\Front\FrontPageBodyResult;
use Illuminate\Contracts\Support\Renderable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FrontPageBodyResultTest extends TestCase
{
    public function test_can_create_with_minimum_arguments(): void
    {
        $result = new FrontPageBodyResult(content: '<p>Hello</p>');

        $this->assertSame('<p>Hello</p>', $result->content);
        $this->assertNull($result->title);
        $this->assertNull($result->description);
        $this->assertSame([], $result->styleUrls);
        $this->assertSame([], $result->scriptUrls);
        $this->assertSame([], $result->meta);
    }

    public function test_html_returns_a_string_body_unchanged(): void
    {
        $result = new FrontPageBodyResult(content: '<p>Hello</p>');

        $this->assertSame('<p>Hello</p>', $result->html());
    }

    public function test_html_renders_a_renderable_body(): void
    {
        $renderable = new class implements Renderable
        {
            public function render(): string
            {
                return '<section>rendered</section>';
            }
        };

        $result = new FrontPageBodyResult(content: $renderable);

        $this->assertSame('<section>rendered</section>', $result->html());
    }

    public function test_is_empty_detects_a_body_with_nothing_to_show(): void
    {
        $this->assertTrue((new FrontPageBodyResult(content: ''))->isEmpty());
        $this->assertTrue((new FrontPageBodyResult(content: "  \n\t "))->isEmpty());
        $this->assertFalse((new FrontPageBodyResult(content: '<p>x</p>'))->isEmpty());
    }

    public function test_is_empty_renders_a_renderable_before_deciding(): void
    {
        $blank = new class implements Renderable
        {
            public function render(): string
            {
                return "\n  ";
            }
        };

        $this->assertTrue((new FrontPageBodyResult(content: $blank))->isEmpty());
    }

    public function test_json_serialize_contains_all_keys_and_renders_the_body(): void
    {
        $renderable = new class implements Renderable
        {
            public function render(): string
            {
                return '<p>body</p>';
            }
        };

        $result = new FrontPageBodyResult(
            content: $renderable,
            title: 'Blog',
            description: 'Latest posts',
            styleUrls: ['/css/blog.css'],
            scriptUrls: ['/js/blog.js'],
            meta: ['foo' => 'bar'],
        );

        $this->assertSame([
            'content' => '<p>body</p>',
            'title' => 'Blog',
            'description' => 'Latest posts',
            'style_urls' => ['/css/blog.css'],
            'script_urls' => ['/js/blog.js'],
            'meta' => ['foo' => 'bar'],
        ], $result->jsonSerialize());
    }

    public function test_dto_is_readonly(): void
    {
        $this->assertTrue((new ReflectionClass(FrontPageBodyResult::class))->isReadOnly());
    }

    /**
     * A canonical URL, a response header and an admin edit link are deliberately not
     * carried: a second `<link rel="canonical">` is not an override, a theme owns no
     * response, and the front page's edit link is core's. Adding a field later is
     * additive; removing one is breaking, so the shape is pinned here.
     */
    public function test_the_result_carries_only_what_a_theme_can_apply(): void
    {
        $names = array_map(
            fn (\ReflectionProperty $p) => $p->getName(),
            (new ReflectionClass(FrontPageBodyResult::class))->getProperties(),
        );

        sort($names);

        $this->assertSame(
            ['content', 'description', 'meta', 'scriptUrls', 'styleUrls', 'title'],
            $names,
        );
    }
}
