{{--
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}


<!-- Title -->
<div class="mb-4">
    @include('components::form.label', [
        'for' => 'title',
        'text' => 'admin.features.contents.pages.title',
    ])
    @include('components::form.text', [
        'id' => 'title',
        'name' => 'title',
        'value' => old('title', $page->title ?? ''),
        'required' => true,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('title')
    ])
</div>

<!-- URL Slug -->
<div class="mb-4">
    @include('components::form.label', [
        'for' => 'slug',
        'text' => 'admin.features.contents.pages.slug',
    ])
    @include('components::form.text', [
        'id' => 'slug',
        'name' => 'slug',
        'value' => old('slug', $page->slug ?? ''),
        'required' => true,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('slug')
    ])
    <p class="text-sm text-gray-500 mt-1">例: "page1" → URL: https://example.com/page1</p>
</div>

<!-- Content -->
<div class="mb-4">
    @include('components::form.label', [
        'for' => 'content',
        'text' => 'admin.features.contents.pages.content',
    ])
    @include('components::form.textarea', [
        'id' => 'content',
        'name' => 'content',
        'value' => old('content', $page->content ?? ''),
        'required' => true,
    ])
</div>

<!-- Staus -->
<div class="mb-4">
    @include('components::form.label', [
        'for' => 'status',
        'text' => 'admin.features.contents.pages.status',
    ])
    @include('components::form.select', [
        'id' => 'status',
        'name' => 'status',
        'value' => old('status', $page->status ?? ''),
        'options' => config('admin.status.pages'),
        'required' => true,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('status')
    ])
