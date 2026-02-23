{{--
This file is part of Dixlase Legal.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

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

@extends('layouts.admin')

@section('content')
    <form id="legal-content-form"
          action="{{ route('dixlase-legal::admin.legal-pages.contents.update', ['slug' => $slug, 'lang' => $lang]) }}"
          method="POST">
        @csrf
        @method('PATCH')

        <div class="space-y-6">
            {{-- ページ種別情報 --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <i class="{{ $pageTypeIcon }} text-gray-600 dark:text-gray-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $pageTypeName }}</h3>
                        <x-ui-status-badge :label="$langName" variant="blue" size="xs" />
                    </div>
                </div>
            </div>

            {{-- タイトル --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <x-form-label :for="'title'" :label="__('dixlase-legal::admin/legal-pages/contents.title_label')" />
                <x-form-text
                    id="title"
                    name="title"
                    :value="old('title', $content?->title)"
                    :placeholder="__('dixlase-legal::admin/legal-pages/contents.title_placeholder')"
                />
                <x-form-error name="title" />
            </div>

            {{-- エディタータイプ --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <x-form-label :label="__('dixlase-legal::admin/legal-pages/contents.editor_type_label')" class="mb-3" />
                <x-form-radio-card-group
                    name="editor_type"
                    :options="[
                        ['value' => 'html', 'label' => $editorOptions['html'] ?? 'HTML', 'icon' => 'fas fa-code', 'description' => __('dixlase-legal::admin/legal-pages/contents.editor_html_description')],
                        ['value' => 'markdown', 'label' => $editorOptions['markdown'] ?? 'Markdown', 'icon' => 'fab fa-markdown', 'description' => __('dixlase-legal::admin/legal-pages/contents.editor_markdown_description')],
                    ]"
                    :value="old('editor_type', $content?->editor_type?->value ?? 'html')"
                    :columns="2"
                />
                <x-form-error name="editor_type" />
            </div>

            {{-- コンテンツ --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <x-form-label :for="'content'" :label="__('dixlase-legal::admin/legal-pages/contents.content_label')" />
                <x-form-textarea
                    id="content"
                    name="content"
                    :value="old('content', $content?->getContentByEditorType())"
                    rows="20"
                    :placeholder="__('dixlase-legal::admin/legal-pages/contents.content_placeholder')"
                    class="font-mono text-sm"
                />
                <x-form-error name="content" />
            </div>

            {{-- ステータス --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <x-form-label :label="__('dixlase-legal::admin/legal-pages/contents.status_label')" class="mb-3" />
                <x-form-radio-card-group
                    name="status"
                    :options="collect($statusOptions)->map(fn ($opt, $val) => [
                        'value' => $val,
                        'label' => $opt['label'],
                        'description' => $opt['description'],
                    ])->values()->all()"
                    :value="old('status', $content?->status?->value ?? 'draft')"
                    :columns="3"
                />
                <x-form-error name="status" />
            </div>

            {{-- 公開日時（scheduled 時のみ） --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
                 x-data="{ status: '{{ old('status', $content?->status?->value ?? 'draft') }}' }"
                 x-show="status === 'scheduled'"
                 x-on:change.window="status = document.querySelector('input[name=status]:checked')?.value || status"
            >
                <x-form-label :for="'published_at'" :label="__('dixlase-legal::admin/legal-pages/contents.published_at_label')" />
                <x-form-text
                    id="published_at"
                    name="published_at"
                    type="datetime-local"
                    :value="old('published_at', $content?->published_at?->format('Y-m-d\TH:i'))"
                />
                <x-form-error name="published_at" />
                <x-form-help-text :text="__('dixlase-legal::admin/legal-pages/contents.published_at_help')" />
            </div>
        </div>
    </form>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmLegalContentModal"
        :label="__('common.save')"
        :title="__('dixlase-legal::admin/legal-pages/contents.confirm_title')"
        :message="__('dixlase-legal::admin/legal-pages/contents.confirm_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="legal-content-form"
    />
@endsection
