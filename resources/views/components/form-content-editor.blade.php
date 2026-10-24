{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-form-content-editor />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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

{{--
Shared content editor for every content screen (the front page, and
plugins such as DixlasePages and DixlaseBlog). The Alpine side is the
registered contentEditor component (resources/src/admin/js/content-editor.js);
its settings travel as JSON in data-config, so x-data stays a bare name.

Optional parts:
  - preview + previewFrameUrl (+ previewUrl): split-pane live preview.
    previewRender 'auto' renders HTML and Markdown in the browser and the
    rest through previewUrl; 'server' sends everything to previewUrl.
    Without a preview, Markdown keeps its side-by-side rendered view
    unless inlineMarkdownPreview is false (a page with its own preview).
  - assetTabs: CSS / JS tabs for the HTML editor (customCss, customJs).
    With markdownCss as well, the Markdown editor gets the CSS tab (never
    the JS one); storing and serving that CSS is the page's job.
  - editors: a fixed list of editor types, instead of the ones the
    storage type allows.
  - templates + langField: starter content per language and editor type,
    applied when either changes (the language comes from the form field
    named langField).
  - fileStorageBasePath: file storage under one directory
    (content.md / content.html, style.css, script.js), shown by
    <x-content-editor.storage-info>.
  - rightSidebar: tell the admin layout the page has a right sidebar.
  - insertImage (on by default): an "Insert image" button above the text
    field that puts a media library item at the cursor.
--}}

@props([
    'storageType' => 'database',
    'editorType' => 'html',
    'content' => '',
    'identifier' => '',
    'showStorageSelector' => true,
    'showEditorSelector' => true,
    'contentFieldName' => 'content',
    'storageFieldName' => 'storage_type',
    'editorFieldName' => 'editor_type',
    'storageOptions' => [],
    'editorOptions' => [],
    'editorTranslations' => [],
    'editorIcons' => [],
    'pluginEditors' => [],
    'guiEditorInfo' => null,
    'guiEditorAssetHtml' => '',
    'editors' => [],
    'editorHelp' => null,
    'contentLabel' => null,
    'placeholder' => '',
    'preview' => false,
    'inlineMarkdownPreview' => true,
    'previewFrameUrl' => '',
    'previewUrl' => '',
    'previewRender' => 'auto',
    'assetTabs' => false,
    'markdownCss' => false,
    'customCss' => '',
    'customJs' => '',
    'customCssFieldName' => 'custom_css',
    'customJsFieldName' => 'custom_js',
    'cssPlaceholder' => '',
    'jsPlaceholder' => '',
    'templates' => [],
    'langField' => '',
    'lang' => '',
    'fileStorageBasePath' => '',
    'rightSidebar' => false,
    'insertImage' => true,
])

<div data-translations='@json($editorTranslations)'
     data-config="{{ json_encode([
         'storageType' => $storageType,
         'editorType' => $editorType,
         'content' => (string) $content,
         'identifier' => $identifier,
         'hasGuiEditor' => (bool) $guiEditorInfo,
         'editors' => array_values($editors),
         'fileStorageBasePath' => $fileStorageBasePath,
         'preview' => (bool) $preview,
         'inlineMarkdownPreview' => (bool) $inlineMarkdownPreview,
         'previewFrameUrl' => $previewFrameUrl,
         'previewUrl' => $previewUrl,
         'previewRender' => $previewRender,
         'assetTabs' => (bool) $assetTabs,
         'markdownCss' => (bool) $markdownCss,
         'templates' => $templates ?: new \stdClass(),
         'langField' => $langField,
         'lang' => $lang,
         'rightSidebar' => (bool) $rightSidebar,
     ], JSON_INVALID_UTF8_SUBSTITUTE) }}"
     data-preview-show="{{ __('components/content-editor.preview_show') }}"
     data-preview-hide="{{ __('components/content-editor.preview_hide') }}"
     x-data="contentEditor"
     {{ $attributes->merge(['class' => 'dixlase-content-editor space-y-4 min-w-0']) }}>

    {{-- Storage type selector --}}
    @if($showStorageSelector)
    <div class="mb-4">
        <x-form-label :for="$storageFieldName" :text="__('common.content_storage.label')" />

        <x-form-radio-card-group
            :name="$storageFieldName"
            :options="$storageOptions"
            :value="$storageType"
            xModel="storageType"
            :columns="2"
            color="blue"
            variant="filled"
            :showCheck="true"
        />
    </div>
    @else
    <input type="hidden" name="{{ $storageFieldName }}" x-model="storageType">
    @endif

    {{-- Editor type selector. From sm the visible cards share one row in
         equal columns, whatever their number and however narrow the
         column (the right sidebar takes width), so they never wrap. --}}
    @if($showEditorSelector)
    <div class="mb-4">
        <x-form-label :for="$editorFieldName" :text="__('common.content_editor.label')" />

        <div class="grid gap-4 grid-cols-1 sm:grid-cols-none sm:grid-flow-col sm:auto-cols-fr">
            @foreach (\App\Enums\ContentEditorType::radioCardOptions() as $option)
                @if ($editors === [] || in_array($option['value'], $editors, true))
                    <label class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none transition-all duration-150"
                           x-show="isAvailable('{{ $option['value'] }}')"
                           :class="editorCardClass('{{ $option['value'] }}')"
                           @click="selectEditor('{{ $option['value'] }}')">
                        <input type="radio"
                               name="{{ $editorFieldName }}"
                               value="{{ $option['value'] }}"
                               x-model="editorType"
                               :disabled="isEditorDisabled('{{ $option['value'] }}')"
                               class="sr-only">

                        <span class="flex flex-1">
                            <span class="flex flex-col justify-center">
                                <span class="flex items-center gap-2 text-sm font-medium"
                                      :class="editorCardTitleClass('{{ $option['value'] }}')">
                                    <i class="{{ $editorIcons[$option['value']] ?? $option['icon'] }}"></i>
                                    <span>{{ $option['label'] }}</span>
                                </span>
                                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $option['description'] }}</span>
                            </span>
                        </span>

                        <span class="absolute top-3 right-3 flex items-center justify-center"
                              x-show="isEditorCardChecked('{{ $option['value'] }}')" x-cloak>
                            <i class="fas fa-check-circle text-lg text-blue-600 dark:text-blue-400"></i>
                        </span>
                    </label>
                @endif
            @endforeach
        </div>
        @if ($editorHelp)
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-info-circle mr-1"></i>{{ $editorHelp }}
            </p>
        @endif
    </div>
    @else
    <input type="hidden" name="{{ $editorFieldName }}" x-model="editorType">
    @endif

    {{-- File storage info (pages that show it in a sidebar use
         <x-content-editor.storage-info> instead) --}}
    @if ($showStorageSelector)
    <div x-show="isFileStorage" x-cloak class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
            <div class="flex-1">
                <div class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                    {{ __('common.content_storage.file_info_title') }}
                </div>
                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <p>{{ __('common.content_storage.file_info_description') }}</p>
                    <p class="font-mono text-xs bg-white dark:bg-gray-800 p-2 rounded mt-2" x-text="filePath"></p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Preview toggle --}}
    @if ($preview)
    <div class="flex flex-wrap items-center gap-3 text-xs">
        <button type="button" @click="togglePreview()"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border transition-colors"
            :class="previewToggleClass()"
            :title="previewToggleLabel()">
            <i :class="previewToggleIcon()"></i>
            <span x-text="previewToggleLabel()"></span>
        </button>
    </div>
    @endif

    {{-- Split pane: editor, and the preview when there is one --}}
    <div x-ref="splitContainer" class="flex gap-4 overflow-hidden" :class="splitContainerClass()">
        <div x-ref="editorPane" class="w-full min-w-0" :class="editorPaneClass()" :style="editorPaneStyle()">
            <div class="space-y-4">
                @if ($contentLabel !== false)
                    <x-form-label :for="$contentFieldName" :text="$contentLabel ?? __('common.content')" />
                @endif

                {{-- CSS / JS tabs (HTML editor; CSS only for Markdown with markdownCss) --}}
                @if ($assetTabs)
                    <div x-show="showAssetTabs" x-cloak>
                        <nav class="border-b border-gray-200 dark:border-gray-600">
                            <div class="flex -mb-px space-x-4">
                                <button type="button" @click="showTab('content')"
                                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                                        :class="tabClass('content')">
                                    <i class="fas fa-code mr-1.5"></i>{{ __('components/content-editor.tab_content') }}
                                </button>
                                <button type="button" @click="showTab('css')"
                                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                                        :class="tabClass('css')">
                                    <i class="fab fa-css3-alt mr-1.5"></i>{{ __('components/content-editor.tab_css') }}
                                </button>
                                <button type="button" @click="showTab('js')"
                                        x-show="showJsTabButton"
                                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                                        :class="tabClass('js')">
                                    <i class="fab fa-js mr-1.5"></i>{{ __('components/content-editor.tab_js') }}
                                </button>
                            </div>
                        </nav>
                    </div>
                @endif

                {{-- GUI editor (plugin-provided). Placed before the text
                     field: with both in the form the later field wins,
                     and the text field is disabled while GUI is chosen. --}}
                <div x-show="isGuiEditor" x-cloak>
                    @if($guiEditorInfo)
                        @include($guiEditorInfo->viewName, [
                            'contentFieldName' => $contentFieldName,
                            'editorInfo' => $guiEditorInfo,
                            'initialContent' => $content,
                        ])
                    @else
                        {{-- No GUI editor plugin: keep the stored content
                             in the form so saving does not drop it. --}}
                        <div class="p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-center">
                            <i class="fas fa-paint-brush text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ (string) $content !== '' ? __('common.content_editor.gui_unavailable') : __('common.content_editor.gui_coming_soon') }}
                            </p>
                        </div>
                        <input type="hidden" name="{{ $contentFieldName }}" x-model="content" :disabled="isTextEditor">
                    @endif
                </div>

                {{-- Markdown / HTML / Blade: one field --}}
                <div x-show="showContentTab">
                    @if ($insertImage)
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <x-content-editor.insert-image
                                :target="$contentFieldName"
                                :selectorId="$contentFieldName.'_image_selector'"
                            />
                        </div>
                    @endif
                    <div :class="contentGridClass()">
                        <div class="min-w-0">
                            <textarea id="{{ $contentFieldName }}"
                                      name="{{ $contentFieldName }}"
                                      rows="10"
                                      placeholder="{{ $placeholder }}"
                                      class="input-common input-full font-mono text-sm"
                                      data-editor-field="content"
                                      :data-editor-type="editorType"
                                      data-auto-resize
                                      x-model="content"
                                      :disabled="isGuiEditor">{{ $content }}</textarea>
                        </div>
                        <div x-show="showInlineMarkdownPreview" x-cloak class="min-w-0">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('common.content_editor.preview') }}
                            </div>
                            <div class="min-h-96 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 prose dark:prose-invert max-w-none overflow-auto"
                                 x-html="markdownHtml()"></div>
                        </div>
                    </div>
                    <div x-show="isEditorSelected('blade')" x-cloak class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                        {{ __('common.content_editor.blade_warning') }}
                    </div>
                </div>

                @if ($assetTabs)
                    <div x-show="showCssTab" x-cloak>
                        <textarea id="{{ $customCssFieldName }}"
                                  name="{{ $customCssFieldName }}"
                                  rows="6"
                                  placeholder="{{ $cssPlaceholder }}"
                                  class="input-common input-full font-mono text-sm"
                                  data-editor-field="css"
                                  data-auto-resize>{{ $customCss }}</textarea>
                        <x-form-error :name="$customCssFieldName" />
                    </div>
                    <div x-show="showJsTab" x-cloak>
                        <textarea id="{{ $customJsFieldName }}"
                                  name="{{ $customJsFieldName }}"
                                  rows="6"
                                  placeholder="{{ $jsPlaceholder }}"
                                  class="input-common input-full font-mono text-sm"
                                  data-editor-field="js"
                                  data-auto-resize>{{ $customJs }}</textarea>
                        <x-form-error :name="$customJsFieldName" />
                    </div>
                @endif
            </div>
        </div>

        @if ($preview)
            @include('components.content-editor.preview-pane')
        @endif
    </div>

    @if ($preview)
        @include('components.content-editor.scroll-buttons')
    @endif
</div>

{{-- Plugin editor assets --}}
@if($guiEditorAssetHtml)
    {!! $guiEditorAssetHtml !!}
@endif
