{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
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
])

@php
use App\Enums\ContentStorageType;
use App\Enums\ContentEditorType;

$storageTypeEnum = is_string($storageType) ? ContentStorageType::from($storageType) : $storageType;
$editorTypeEnum = is_string($editorType) ? ContentEditorType::from($editorType) : $editorType;
@endphp

<div x-data="{
    storageType: '{{ $storageTypeEnum->value }}',
    editorType: '{{ $editorTypeEnum->value }}',
    content: @js($content),
    identifier: @js($identifier),
    
    get availableEditors() {
        const editors = {
            'database': ['gui', 'markdown', 'html'],
            'file': ['blade', 'markdown', 'html']
        };
        return editors[this.storageType] || [];
    },
    
    get isFileStorage() {
        return this.storageType === 'file';
    },
    
    get filePath() {
        if (!this.isFileStorage || !this.identifier) return '';
        const extensions = {
            'blade': 'blade.php',
            'markdown': 'md',
            'html': 'html',
            'gui': 'json'
        };
        const ext = extensions[this.editorType] || 'txt';
        return `storage/app/pages/${this.identifier}.${ext}`;
    },
    
    updateEditorType() {
        // 保存方法変更時、利用可能なエディタータイプでなければ最初のものを選択
        if (!this.availableEditors.includes(this.editorType)) {
            this.editorType = this.availableEditors[0] || 'html';
        }
    }
}" x-init="$watch('storageType', () => updateEditorType())" class="space-y-4">

    {{-- 保存方法選択 --}}
    @if($showStorageSelector)
    <div class="mb-4">
        @include('components::form.label', [
            'for' => $storageFieldName,
            'text' => __('common.content_storage.label'),
        ])
        
        @php
        $storageOptions = [];
        foreach(ContentStorageType::optionsWithDescription() as $value => $option) {
            $storageOptions[] = [
                'value' => $value,
                'label' => $option['label'],
                'description' => $option['description'],
                'icon' => $value === 'database' ? 'fas fa-database' : 'fas fa-file-code',
            ];
        }
        @endphp
        
        <x-form.radio_card_group
            :name="$storageFieldName"
            :options="$storageOptions"
            :value="$storageTypeEnum->value"
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

    {{-- エディタータイプ選択 --}}
    @if($showEditorSelector)
    <div class="mb-4">
        @include('components::form.label', [
            'for' => $editorFieldName,
            'text' => __('common.content_editor.label'),
        ])
        
        @php
        $editorIcons = [
            'gui' => 'fas fa-magic',
            'markdown' => 'fab fa-markdown',
            'html' => 'fas fa-code',
            'blade' => 'fab fa-laravel',
        ];
        $editorColors = [
            'gui' => 'purple',
            'markdown' => 'blue',
            'html' => 'orange',
            'blade' => 'red',
        ];
        @endphp
        
        <div x-data="{
            editorIcons: {{ Js::from($editorIcons) }},
            editorColors: {{ Js::from($editorColors) }},
            editorOptions: [],
            updateEditorOptions() {
                this.editorOptions = this.availableEditors.map(editor => ({
                    value: editor,
                    label: this.$t(`common.content_editor.${editor}`),
                    description: this.$t(`common.content_editor.${editor}_description`),
                    icon: this.editorIcons[editor] || 'fas fa-file',
                    color: this.editorColors[editor] || 'gray',
                    disabled: editor === 'gui'
                }));
            }
        }" x-init="updateEditorOptions(); $watch('availableEditors', () => updateEditorOptions())">
            <template x-if="editorOptions.length > 0">
                <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <template x-for="option in editorOptions" :key="option.value">
                        <label class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none transition-all duration-150"
                               :class="[
                                   option.disabled ? 'opacity-50 cursor-not-allowed' : '',
                                   editorType === option.value 
                                       ? 'border-blue-600 dark:border-blue-500 ring-3 ring-blue-600 dark:ring-blue-500 bg-blue-50 dark:bg-blue-900/30' 
                                       : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-500'
                               ]"
                               @click="if (!option.disabled) editorType = option.value">
                            <input type="radio" 
                                   name="{{ $editorFieldName }}" 
                                   :value="option.value"
                                   x-model="editorType"
                                   :disabled="option.disabled"
                                   class="sr-only">
                            
                            <span class="flex flex-1">
                                <span class="flex flex-col justify-center">
                                    <span class="flex items-center gap-2 text-sm font-medium"
                                          :class="editorType === option.value ? 'text-blue-700 dark:text-blue-300' : 'text-gray-900 dark:text-white'">
                                        <i :class="option.icon"></i>
                                        <span x-text="option.label"></span>
                                    </span>
                                    <span class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="option.description"></span>
                                </span>
                            </span>
                            
                            <span class="absolute top-3 right-3 flex items-center justify-center"
                                  x-show="editorType === option.value && !option.disabled"
                                  x-transition:enter="transition ease-out duration-100"
                                  x-transition:enter-start="opacity-0 scale-75"
                                  x-transition:enter-end="opacity-100 scale-100"
                                  x-transition:leave="transition ease-in duration-75"
                                  x-transition:leave-start="opacity-100 scale-100"
                                  x-transition:leave-end="opacity-0 scale-75">
                                <i class="fas fa-check-circle text-lg text-blue-600 dark:text-blue-400"></i>
                            </span>
                            
                            <span class="pointer-events-none absolute -inset-px rounded-lg" 
                                  :class="editorType === option.value ? 'border-2 border-blue-600 dark:border-blue-500' : 'border border-transparent'"
                                  aria-hidden="true"></span>
                        </label>
                    </template>
                </div>
            </template>
        </div>
    </div>
    @else
    <input type="hidden" name="{{ $editorFieldName }}" x-model="editorType">
    @endif

    {{-- ファイル保存時の情報表示 --}}
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

    {{-- コンテンツエディタ --}}
    <div class="mb-4">
        @include('components::form.label', [
            'for' => $contentFieldName,
            'text' => __('common.content'),
        ])
        
        {{-- GUI エディタ（将来実装） --}}
        <div x-show="editorType === 'gui'" x-cloak>
            <div class="p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-center">
                <i class="fas fa-magic text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('common.content_editor.gui_coming_soon') }}
                </p>
            </div>
            <input type="hidden" name="{{ $contentFieldName }}" x-model="content">
        </div>

        {{-- Markdown エディタ --}}
        <div x-show="editorType === 'markdown'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('common.content_editor.markdown_editor') }}
                    </div>
                    @include('components::form.textarea', [
                        'id' => $contentFieldName . '_markdown',
                        'name' => $contentFieldName,
                        'value' => $content,
                        'class' => 'min-h-96 font-mono text-sm',
                        'xModel' => 'content',
                    ])
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('common.content_editor.preview') }}
                    </div>
                    <div class="min-h-96 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 prose dark:prose-invert max-w-none overflow-auto"
                         x-html="marked.parse(content || '')"></div>
                </div>
            </div>
        </div>

        {{-- HTML エディタ --}}
        <div x-show="editorType === 'html'" x-cloak>
            @include('components::form.textarea', [
                'id' => $contentFieldName . '_html',
                'name' => $contentFieldName,
                'value' => $content,
                'class' => 'min-h-96 font-mono text-sm',
                'xModel' => 'content',
            ])
        </div>

        {{-- Blade エディタ --}}
        <div x-show="editorType === 'blade'" x-cloak>
            @include('components::form.textarea', [
                'id' => $contentFieldName . '_blade',
                'name' => $contentFieldName,
                'value' => $content,
                'class' => 'min-h-96 font-mono text-sm',
                'xModel' => 'content',
            ])
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                {{ __('common.content_editor.blade_warning') }}
            </div>
        </div>
    </div>
</div>

@php
$editorTranslations = [
    'common.content_editor.gui' => __('common.content_editor.gui'),
    'common.content_editor.gui_description' => __('common.content_editor.gui_description'),
    'common.content_editor.markdown' => __('common.content_editor.markdown'),
    'common.content_editor.markdown_description' => __('common.content_editor.markdown_description'),
    'common.content_editor.html' => __('common.content_editor.html'),
    'common.content_editor.html_description' => __('common.content_editor.html_description'),
    'common.content_editor.blade' => __('common.content_editor.blade'),
    'common.content_editor.blade_description' => __('common.content_editor.blade_description'),
];
@endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script @cspNonce>
// Alpine.js用の翻訳ヘルパー
document.addEventListener('alpine:init', () => {
    Alpine.magic('t', () => {
        return (key) => {
            const translations = @json($editorTranslations);
            return translations[key] || key;
        };
    });
});
</script>
@endpush
