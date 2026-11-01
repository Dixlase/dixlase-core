<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Feature\Admin;

use App\Models\MediaTag;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * <x-form-tag-input> (#841) takes its tag list from the URL it is given and
 * passes its options to tagInput; <x-media.tag-input> is the same component
 * pointed at the media tags.
 */
class FormTagInputComponentTest extends TestCase
{
    public function test_it_reads_its_tags_from_the_given_url(): void
    {
        $html = Blade::render(
            '<x-form-tag-input id="postTags" tagsUrl="/admin-custom/blog/tags/list" :selected="$selected" />',
            ['selected' => ['news', 'release']],
        );

        $this->assertStringContainsString('x-data="tagInput"', $html);
        $this->assertStringContainsString('data-tags-url="/admin-custom/blog/tags/list"', $html);
        $this->assertStringContainsString('data-selected="[&quot;news&quot;,&quot;release&quot;]"', $html);
        $this->assertStringContainsString('data-name="tags[]"', $html);
        $this->assertStringContainsString('data-allow-create="1"', $html);
        $this->assertStringContainsString('data-commit-typed="0"', $html);
        $this->assertStringContainsString('data-strip-hash="0"', $html);
        $this->assertStringContainsString('id="postTags-field"', $html);
        $this->assertStringContainsString('aria-controls="postTags-listbox"', $html);
        $this->assertStringNotContainsString('maxlength=', $html);
    }

    public function test_the_texts_default_to_the_generic_ones(): void
    {
        app()->setLocale('en');

        $html = Blade::render('<x-form-tag-input id="t" tagsUrl="/tags" />');

        $this->assertStringContainsString('data-create-label="Create “:name” as a new tag"', $html);
        $this->assertStringContainsString('data-remove-label="Remove :name"', $html);
        $this->assertStringContainsString('placeholder="Add a tag…"', $html);
        $this->assertStringContainsString('No tag matches.', $html);
    }

    public function test_a_free_text_field_commits_what_is_typed_and_drops_a_hash(): void
    {
        $html = Blade::render(
            '<x-form-tag-input id="t" tagsUrl="/tags" :commitTyped="true" :stripHash="true" :maxlength="50" createLabel="Add “:name”" />',
        );

        $this->assertStringContainsString('data-commit-typed="1"', $html);
        $this->assertStringContainsString('data-strip-hash="1"', $html);
        $this->assertStringContainsString('maxlength="50"', $html);
        $this->assertStringContainsString('data-create-label="Add “:name”"', $html);
    }

    public function test_the_media_tag_input_points_at_the_media_tags(): void
    {
        app()->setLocale('en');

        $html = Blade::render('<x-media.tag-input id="mediaTags" :allowCreate="false" valueField="id" form="bulk" :inline="true" />');

        $this->assertStringContainsString('x-data="tagInput"', $html);
        $this->assertStringContainsString('data-tags-url="'.e(route('admin.media.tags.list')).'"', $html);
        $this->assertStringContainsString('maxlength="'.MediaTag::MAX_NAME_LENGTH.'"', $html);
        $this->assertStringContainsString('data-allow-create="0"', $html);
        $this->assertStringContainsString('data-value-field="id"', $html);
        $this->assertStringContainsString('data-form="bulk"', $html);
        $this->assertStringContainsString('class="relative mt-1', $html);
        $this->assertStringContainsString('data-create-label="'.e(__('admin/media/tags.input.create', ['name' => ':name'])).'"', $html);
    }
}
