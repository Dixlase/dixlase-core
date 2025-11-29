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
    'translations' => [], // ['ja' => ['title' => '...', 'content' => '...'], 'en' => [...]]
    'identifier' => '',
    'pageId' => null, // ページID（編集時のファイルコンテンツ取得用）
    'showStorageSelector' => true,
    'showEditorSelector' => true,
    'storageFieldName' => 'storage_type',
    'editorFieldName' => 'editor_type',
    'showMetaDescription' => false,
    'showOgpImage' => false,
    'showSlug' => false,
    'slugFieldName' => 'slug',
    'slugValue' => '',
    'showStatus' => false,
    'statusFieldName' => 'status',
    'statusValue' => 'draft',
    'publishedAtValue' => '',
    'pagesDirectory' => 'pages',
    'baseUrl' => '',
    'multilingualEnabled' => null, // null = auto-detect from base settings
])

@php
use App\Enums\ContentStorageType;
use App\Enums\ContentEditorType;
use App\Helpers\LocaleHelper;
use App\Models\BaseSetting;

$storageTypeEnum = is_string($storageType) ? ContentStorageType::from($storageType) : $storageType;
$editorTypeEnum = is_string($editorType) ? ContentEditorType::from($editorType) : $editorType;

// 多言語設定を取得（プロパティで指定されていない場合は基本設定から取得）
$isMultilingualEnabled = $multilingualEnabled ?? (bool) BaseSetting::get('multilingual_enabled', false);

// 有効な言語を取得（文字列または配列の両方に対応）
$enabledLocalesValue = BaseSetting::get('enabled_locales', '["en"]');
$enabledLocales = is_array($enabledLocalesValue) 
    ? ($enabledLocalesValue ?: ['en'])
    : (json_decode($enabledLocalesValue, true) ?: ['en']);

// 多言語が有効な場合は有効な言語のみを使用、無効な場合はデフォルト言語のみ
if ($isMultilingualEnabled) {
    // 有効な言語のみをフィルタリング
    $allSupportedLocales = LocaleHelper::supportedLocales();
    $supportedLocales = array_values(array_filter($allSupportedLocales, function($locale) use ($enabledLocales) {
        return in_array($locale, $enabledLocales);
    }));
    // 有効な言語がない場合はデフォルト言語（英語）を使用
    if (empty($supportedLocales)) {
        $supportedLocales = ['en'];
    }
} else {
    // 多言語無効時はデフォルト言語のみ
    $defaultLocale = config('app.locale', 'ja');
    $supportedLocales = [$defaultLocale];
}
$userPreferredLocale = LocaleHelper::getUserPreferredLocale();
// ユーザーの優先言語が有効な言語に含まれていない場合は最初の有効な言語を使用
if (!in_array($userPreferredLocale, $supportedLocales)) {
    $userPreferredLocale = $supportedLocales[0];
}
@endphp

<div x-data="{
    storageType: '{{ $storageTypeEnum->value }}',
    editorType: '{{ $editorTypeEnum->value }}',
    currentLocale: '{{ $userPreferredLocale }}',
    identifier: @js($identifier),
    pageId: @js($pageId),
    translations: @js($translations),
    slug: @js($slugValue),
    status: @js($statusValue),
    publishedAt: @js($publishedAtValue),
    pagesDirectory: @js($pagesDirectory),
    baseUrl: @js($baseUrl ?: config('app.url')),
    isLoadingContent: false,
    
    get availableEditors() {
        const editors = {
            'database': ['gui', 'markdown', 'html'],
            'file': ['blade', 'markdown', 'html']
        };
        return editors[this.storageType] || [];
    },
    
    get pageUrl() {
        if (!this.slug) return '';
        return `${this.baseUrl}/${this.pagesDirectory}/${this.slug}`;
    },
    
    get isFileStorage() {
        return this.storageType === 'file';
    },
    
    get isDatabaseOnly() {
        // GUIエディタの場合はDBのみ
        return this.editorType === 'gui';
    },
    
    supportedLocales: @js($supportedLocales),
    
    get filePaths() {
        if (!this.isFileStorage) return [];
        const slugOrId = this.slug || this.identifier || 'untitled';
        const extensions = {
            'blade': 'blade.php',
            'markdown': 'md',
            'html': 'html'
        };
        const ext = extensions[this.editorType] || 'txt';
        
        // 有効な言語ごとのファイルパスを生成
        return this.supportedLocales.map(locale => {
            // 英語(en)はデフォルトファイル、他言語は言語コード付き
            const fileName = locale === 'en' 
                ? `${slugOrId}.${ext}` 
                : `${slugOrId}.${locale}.${ext}`;
            return `storage/app/private/pages/${fileName}`;
        });
    },
    
    getCurrentTranslation(locale) {
        return this.translations[locale] || { title: '', content: '', meta_description: '' };
    },
    
    updateTranslation(locale, field, value) {
        if (!this.translations[locale]) {
            this.translations[locale] = { title: '', content: '', meta_description: '' };
        }
        this.translations[locale][field] = value;
    },
    
    getCompleteness(locale) {
        const trans = this.translations[locale];
        if (!trans) return 0;
        const fields = ['title', 'content'];
        const filled = fields.filter(f => trans[f] && trans[f].trim() !== '').length;
        return Math.round((filled / fields.length) * 100);
    },
    
    async updateEditorType() {
        if (!this.availableEditors.includes(this.editorType)) {
            this.editorType = this.availableEditors[0] || 'html';
        }
    },
    
    // 保存方法またはエディタータイプ変更時にコンテンツを読み込む
    async loadContent(storageType = null, editorType = null) {
        // ページIDがない場合（新規作成時）はスキップ
        if (!this.pageId) {
            return;
        }
        
        const targetStorageType = storageType || this.storageType;
        const targetEditorType = editorType || this.editorType;
        
        this.isLoadingContent = true;
        try {
            const response = await fetch(`/admin/pages/${this.pageId}/content/${targetStorageType}/${targetEditorType}`);
            if (response.ok) {
                const data = await response.json();
                if (data.contents) {
                    // 各言語のコンテンツを更新
                    for (const [locale, content] of Object.entries(data.contents)) {
                        if (!this.translations[locale]) {
                            this.translations[locale] = { title: '', content: '', meta_description: '' };
                        }
                        this.translations[locale].content = content;
                    }
                }
            }
        } catch (error) {
            console.error('Failed to load content:', error);
        } finally {
            this.isLoadingContent = false;
        }
    },
    
    // エディタータイプ変更時にファイルコンテンツを読み込む（後方互換性のため残す）
    async loadFileContent(newEditorType) {
        await this.loadContent(this.storageType, newEditorType);
    },
    
    updateStorageType() {
        // GUIエディタの場合は強制的にDBに
        if (this.editorType === 'gui') {
            this.storageType = 'database';
        }
    },
    
    // 保存方法変更時のハンドラ
    async onStorageTypeChange(newStorageType) {
        this.storageType = newStorageType;
        
        // GUIエディタの場合は強制的にDBに
        if (this.editorType === 'gui' && newStorageType === 'file') {
            this.editorType = 'html';
        }
        
        // 利用可能なエディタに切り替え
        if (!this.availableEditors.includes(this.editorType)) {
            this.editorType = this.availableEditors[0] || 'html';
        }
        
        // コンテンツを読み込む
        await this.loadContent(newStorageType, this.editorType);
    },
    
    // エディタータイプ変更時のハンドラ
    async onEditorTypeChange(newEditorType) {
        this.editorType = newEditorType;
        
        // コンテンツを読み込む
        await this.loadContent(this.storageType, newEditorType);
    },
    
    // スラッグ自動生成
    generateSlug(text) {
        if (!text) return '';
        
        // ローマ字変換マップ（ひらがな・カタカナ）
        const romajiMap = {
            'あ': 'a', 'い': 'i', 'う': 'u', 'え': 'e', 'お': 'o',
            'か': 'ka', 'き': 'ki', 'く': 'ku', 'け': 'ke', 'こ': 'ko',
            'さ': 'sa', 'し': 'shi', 'す': 'su', 'せ': 'se', 'そ': 'so',
            'た': 'ta', 'ち': 'chi', 'つ': 'tsu', 'て': 'te', 'と': 'to',
            'な': 'na', 'に': 'ni', 'ぬ': 'nu', 'ね': 'ne', 'の': 'no',
            'は': 'ha', 'ひ': 'hi', 'ふ': 'fu', 'へ': 'he', 'ほ': 'ho',
            'ま': 'ma', 'み': 'mi', 'む': 'mu', 'め': 'me', 'も': 'mo',
            'や': 'ya', 'ゆ': 'yu', 'よ': 'yo',
            'ら': 'ra', 'り': 'ri', 'る': 'ru', 'れ': 're', 'ろ': 'ro',
            'わ': 'wa', 'を': 'wo', 'ん': 'n',
            'が': 'ga', 'ぎ': 'gi', 'ぐ': 'gu', 'げ': 'ge', 'ご': 'go',
            'ざ': 'za', 'じ': 'ji', 'ず': 'zu', 'ぜ': 'ze', 'ぞ': 'zo',
            'だ': 'da', 'ぢ': 'di', 'づ': 'du', 'で': 'de', 'ど': 'do',
            'ば': 'ba', 'び': 'bi', 'ぶ': 'bu', 'べ': 'be', 'ぼ': 'bo',
            'ぱ': 'pa', 'ぴ': 'pi', 'ぷ': 'pu', 'ぺ': 'pe', 'ぽ': 'po',
            'きゃ': 'kya', 'きゅ': 'kyu', 'きょ': 'kyo',
            'しゃ': 'sha', 'しゅ': 'shu', 'しょ': 'sho',
            'ちゃ': 'cha', 'ちゅ': 'chu', 'ちょ': 'cho',
            'にゃ': 'nya', 'にゅ': 'nyu', 'にょ': 'nyo',
            'ひゃ': 'hya', 'ひゅ': 'hyu', 'ひょ': 'hyo',
            'みゃ': 'mya', 'みゅ': 'myu', 'みょ': 'myo',
            'りゃ': 'rya', 'りゅ': 'ryu', 'りょ': 'ryo',
            'ぎゃ': 'gya', 'ぎゅ': 'gyu', 'ぎょ': 'gyo',
            'じゃ': 'ja', 'じゅ': 'ju', 'じょ': 'jo',
            'びゃ': 'bya', 'びゅ': 'byu', 'びょ': 'byo',
            'ぴゃ': 'pya', 'ぴゅ': 'pyu', 'ぴょ': 'pyo',
            'っ': '',
            'ア': 'a', 'イ': 'i', 'ウ': 'u', 'エ': 'e', 'オ': 'o',
            'カ': 'ka', 'キ': 'ki', 'ク': 'ku', 'ケ': 'ke', 'コ': 'ko',
            'サ': 'sa', 'シ': 'shi', 'ス': 'su', 'セ': 'se', 'ソ': 'so',
            'タ': 'ta', 'チ': 'chi', 'ツ': 'tsu', 'テ': 'te', 'ト': 'to',
            'ナ': 'na', 'ニ': 'ni', 'ヌ': 'nu', 'ネ': 'ne', 'ノ': 'no',
            'ハ': 'ha', 'ヒ': 'hi', 'フ': 'fu', 'ヘ': 'he', 'ホ': 'ho',
            'マ': 'ma', 'ミ': 'mi', 'ム': 'mu', 'メ': 'me', 'モ': 'mo',
            'ヤ': 'ya', 'ユ': 'yu', 'ヨ': 'yo',
            'ラ': 'ra', 'リ': 'ri', 'ル': 'ru', 'レ': 're', 'ロ': 'ro',
            'ワ': 'wa', 'ヲ': 'wo', 'ン': 'n',
            'ガ': 'ga', 'ギ': 'gi', 'グ': 'gu', 'ゲ': 'ge', 'ゴ': 'go',
            'ザ': 'za', 'ジ': 'ji', 'ズ': 'zu', 'ゼ': 'ze', 'ゾ': 'zo',
            'ダ': 'da', 'ヂ': 'di', 'ヅ': 'du', 'デ': 'de', 'ド': 'do',
            'バ': 'ba', 'ビ': 'bi', 'ブ': 'bu', 'ベ': 'be', 'ボ': 'bo',
            'パ': 'pa', 'ピ': 'pi', 'プ': 'pu', 'ペ': 'pe', 'ポ': 'po',
            'ッ': ''
        };
        
        let result = text.toLowerCase();
        
        // 2文字の拗音を先に変換
        const twoCharPatterns = Object.keys(romajiMap).filter(k => k.length === 2).sort((a, b) => b.length - a.length);
        for (const pattern of twoCharPatterns) {
            result = result.split(pattern).join(romajiMap[pattern]);
        }
        
        // 1文字のひらがな・カタカナを変換
        for (const [kana, romaji] of Object.entries(romajiMap)) {
            if (kana.length === 1) {
                result = result.split(kana).join(romaji);
            }
        }
        
        // 漢字などの非ASCII文字を削除（ローマ字変換できないもの）
        result = result.replace(/[^\x00-\x7F]/g, '');
        
        // 空白、アンダースコアをハイフンに変換
        result = result.replace(/[\s_]+/g, '-');
        
        // 英数字とハイフン以外を削除
        result = result.replace(/[^a-z0-9-]/g, '');
        
        // 連続するハイフンを1つに
        result = result.replace(/-+/g, '-');
        
        // 先頭と末尾のハイフンを削除
        result = result.replace(/^-+|-+$/g, '');
        
        return result;
    },
    
    autoGenerateSlug() {
        // スラッグが空の場合、現在の言語のタイトルからスラッグを生成
        if (!this.slug || this.slug.trim() === '') {
            const currentTitle = this.translations[this.currentLocale]?.title || '';
            if (currentTitle) {
                this.slug = this.generateSlug(currentTitle);
            }
        }
    }
}" x-init="$watch('storageType', () => updateEditorType()); $watch('editorType', () => updateStorageType())" class="space-y-4">

    {{-- 1. 言語タブ（多言語有効時のみ表示） --}}
    @if($isMultilingualEnabled && count($supportedLocales) > 1)
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex space-x-4" aria-label="Tabs">
            @foreach($supportedLocales as $locale)
            <button type="button"
                    @click="currentLocale = '{{ $locale }}'"
                    :class="currentLocale === '{{ $locale }}' 
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                <span>{{ LocaleHelper::getLocaleName($locale, true) }}</span>
                <span x-show="getCompleteness('{{ $locale }}') > 0" 
                      :class="getCompleteness('{{ $locale }}') === 100 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'"
                      class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium"
                      x-text="getCompleteness('{{ $locale }}') + '%'"></span>
            </button>
            @endforeach
        </nav>
    </div>
    @endif

    {{-- 2. 各言語のタイトル --}}
    @foreach($supportedLocales as $locale)
    <div x-show="currentLocale === '{{ $locale }}'" x-cloak>
        @include('components::form.label', [
            'for' => "title_{$locale}",
            'text' => __('common.title'),
            'required' => true,
        ])
        @include('components::form.text', [
            'id' => "title_{$locale}",
            'name' => "translations[{$locale}][title]",
            'value' => old("translations.{$locale}.title", $translations[$locale]['title'] ?? ''),
            'xModel' => "translations.{$locale}.title",
            'xOn' => "input: updateTranslation('{$locale}', 'title', \$event.target.value); blur: autoGenerateSlug()",
        ])
    </div>
    @endforeach

    {{-- 3. エディタータイプ選択 --}}
    @if($showEditorSelector)
    <div class="mb-4">
        @include('components::form.label', [
            'for' => $editorFieldName,
            'text' => __('common.content_editor.label'),
        ])
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <template x-for="editor in availableEditors" :key="editor">
                <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                       :class="editorType === editor ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'">
                    <input type="radio" 
                           name="{{ $editorFieldName }}" 
                           :value="editor"
                           x-model="editorType"
                           @change="onEditorTypeChange(editor)"
                           class="mt-1 mr-3">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-gray-100" x-text="$t(`common.content_editor.${editor}`)"></div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1" x-text="$t(`common.content_editor.${editor}_description`)"></div>
                    </div>
                </label>
            </template>
        </div>
    </div>
    @else
    <input type="hidden" name="{{ $editorFieldName }}" x-model="editorType">
    @endif

    {{-- 4. 各言語のコンテンツエディタ --}}
    @foreach($supportedLocales as $locale)
    <div x-show="currentLocale === '{{ $locale }}'" x-cloak class="space-y-4">
        <div>
            @include('components::form.label', [
                'for' => "content_{$locale}",
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
            </div>

            {{-- Markdown エディタ --}}
            <div x-show="editorType === 'markdown'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('common.content_editor.markdown_editor') }}
                        </div>
                        @include('components::form.textarea', [
                            'id' => "content_markdown_{$locale}",
                            'class' => 'min-h-96 font-mono text-sm',
                            'xModel' => "translations.{$locale}.content",
                        ])
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('common.content_editor.preview') }}
                        </div>
                        <div class="min-h-96 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 prose dark:prose-invert max-w-none overflow-auto"
                             x-html="marked.parse(translations.{{ $locale }}.content || '')"></div>
                    </div>
                </div>
            </div>

            {{-- HTML エディタ --}}
            <div x-show="editorType === 'html'" x-cloak>
                @include('components::form.textarea', [
                    'id' => "content_html_{$locale}",
                    'class' => 'min-h-96 font-mono text-sm',
                    'xModel' => "translations.{$locale}.content",
                ])
            </div>

            {{-- Blade エディタ --}}
            <div x-show="editorType === 'blade'" x-cloak>
                @include('components::form.textarea', [
                    'id' => "content_blade_{$locale}",
                    'class' => 'min-h-96 font-mono text-sm',
                    'xModel' => "translations.{$locale}.content",
                ])
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                    {{ __('common.content_editor.blade_warning') }}
                </div>
            </div>
            
            {{-- コンテンツ用の隠しフィールド（Alpine.jsのデータをフォームに送信） --}}
            <input type="hidden" 
                   name="translations[{{ $locale }}][content]" 
                   :value="translations['{{ $locale }}']?.content || ''">
        </div>
    </div>
    @endforeach

    {{-- 5. スラッグ --}}
    @if($showSlug)
    <div class="mb-4">
        @include('components::form.label', [
            'for' => $slugFieldName,
            'text' => __('common.slug'),
        ])
        <div class="flex gap-2">
            <div class="flex-1">
                @include('components::form.text', [
                    'id' => $slugFieldName,
                    'name' => $slugFieldName,
                    'value' => old($slugFieldName, $slugValue),
                    'placeholder' => 'about-us',
                    'xModel' => 'slug',
                ])
            </div>
            <button type="button" 
                    @click="autoGenerateSlug()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                <i class="fas fa-sync-alt mr-1"></i>
                {{ __('common.auto_generate') }}
            </button>
        </div>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('common.slug_help') }}
        </p>
        
        {{-- URL表示 --}}
        <div x-show="slug" x-cloak class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center text-sm">
                <span class="text-gray-500 dark:text-gray-400 mr-2">{{ __('common.page_url') }}:</span>
                <a :href="pageUrl" 
                   target="_blank" 
                   class="text-blue-600 dark:text-blue-400 hover:underline font-mono text-xs break-all"
                   x-text="pageUrl"></a>
                <button type="button" 
                        @click="navigator.clipboard.writeText(pageUrl); $dispatch('notify', {message: '{{ __('common.copied') }}', type: 'success'})"
                        class="ml-2 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        title="{{ __('common.copy') }}">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- 6. 保存方法選択 --}}
    @if($showStorageSelector)
    <div class="mb-4">
        @include('components::form.label', [
            'for' => $storageFieldName,
            'text' => __('common.content_storage.label'),
        ])
        
        {{-- GUIエディタの場合はDB固定 --}}
        <div x-show="isDatabaseOnly" x-cloak class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
            <div class="flex items-center text-gray-700 dark:text-gray-300">
                <i class="fas fa-database mr-2"></i>
                <span>{{ __('common.content_storage.database') }}</span>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ __('common.content_storage.gui_db_only') }})</span>
            </div>
            <input type="hidden" name="{{ $storageFieldName }}" value="database">
        </div>
        
        {{-- 通常の保存方法選択 --}}
        <div x-show="!isDatabaseOnly" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach(ContentStorageType::optionsWithDescription() as $value => $option)
            <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                   :class="storageType === '{{ $value }}' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'">
                <input type="radio" 
                       name="{{ $storageFieldName }}" 
                       value="{{ $value }}"
                       x-model="storageType"
                       @change="onStorageTypeChange('{{ $value }}')"
                       class="mt-1 mr-3">
                <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        {{ $option['label'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $option['description'] }}
                    </div>
                </div>
            </label>
            @endforeach
        </div>
        
        {{-- ファイル保存時の情報表示 --}}
        <div x-show="isFileStorage && !isDatabaseOnly" x-cloak class="mt-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                <div class="flex-1">
                    <div class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                        {{ __('common.content_storage.file_info_title') }}
                    </div>
                    <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                        <p>{{ __('common.content_storage.file_info_description') }}</p>
                        <div class="font-mono text-xs bg-white dark:bg-gray-800 p-2 rounded mt-2 space-y-1">
                            <template x-for="path in filePaths" :key="path">
                                <p x-text="path"></p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <input type="hidden" name="{{ $storageFieldName }}" x-model="storageType">
    @endif

    {{-- 7. OGP画像（オプション） --}}
    @if($showOgpImage)
    @foreach($supportedLocales as $locale)
    <div x-show="currentLocale === '{{ $locale }}'" x-cloak>
        @include('components.media-picker', [
            'name' => "translations[{$locale}][ogp_image_id]",
            'label' => __('common.ogp_image'),
            'value' => old("translations.{$locale}.ogp_image_id", $translations[$locale]['ogp_image_id'] ?? ''),
            'aspectRatio' => 'ogp'
        ])
    </div>
    @endforeach
    @endif

    {{-- メタディスクリプション（オプション） --}}
    @if($showMetaDescription)
    @foreach($supportedLocales as $locale)
    <div x-show="currentLocale === '{{ $locale }}'" x-cloak>
        @include('components::form.label', [
            'for' => "meta_description_{$locale}",
            'text' => __('common.meta_description'),
        ])
        @include('components::form.textarea', [
            'id' => "meta_description_{$locale}",
            'name' => "translations[{$locale}][meta_description]",
            'value' => old("translations.{$locale}.meta_description", $translations[$locale]['meta_description'] ?? ''),
            'rows' => 3,
            'xModel' => "translations.{$locale}.meta_description",
        ])
    </div>
    @endforeach
    @endif

    {{-- 8. 状態 --}}
    @if($showStatus)
    <div class="mb-4">
        @include('components::form.label', [
            'for' => $statusFieldName,
            'text' => __('common.status'),
        ])
        @include('components::form.radio-group', [
            'name' => $statusFieldName,
            'options' => [
                'draft' => __('components.status.draft'),
                'published' => __('components.status.published'),
                'scheduled' => __('components.status.scheduled'),
            ],
            'value' => old($statusFieldName, $statusValue),
            'xModel' => 'status',
        ])
        
        {{-- 公開日時（予約投稿時のみ表示） --}}
        <div x-show="status === 'scheduled'" x-cloak class="mt-3">
            @include('components::form.label', [
                'for' => 'published_at',
                'text' => __('common.published_at'),
            ])
            @include('components::form.text', [
                'id' => 'published_at',
                'name' => 'published_at',
                'type' => 'datetime-local',
                'value' => old('published_at', $publishedAtValue),
                'xModel' => 'publishedAt',
            ])
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
// Alpine.js用の翻訳ヘルパー
document.addEventListener('alpine:init', () => {
    Alpine.magic('t', () => {
        return (key) => {
            const translations = {
                'common.content_editor.gui': @json(__('common.content_editor.gui')),
                'common.content_editor.gui_description': @json(__('common.content_editor.gui_description')),
                'common.content_editor.markdown': @json(__('common.content_editor.markdown')),
                'common.content_editor.markdown_description': @json(__('common.content_editor.markdown_description')),
                'common.content_editor.html': @json(__('common.content_editor.html')),
                'common.content_editor.html_description': @json(__('common.content_editor.html_description')),
                'common.content_editor.blade': @json(__('common.content_editor.blade')),
                'common.content_editor.blade_description': @json(__('common.content_editor.blade_description'))
            };
            return translations[key] || key;
        };
    });
});
</script>
@endpush
