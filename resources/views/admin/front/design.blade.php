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

@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">{{ __('admin.nav.front.design') }}</h1>

    @php
    use App\Helpers\LocaleHelper;
    use App\Models\FrontPage;

    // フロントページのメインコンテンツを取得または作成
    $frontPage = FrontPage::findOrCreateByType('main_content');
    
    // 翻訳データの準備
    $translations = [];
    foreach (LocaleHelper::supportedLocales() as $locale) {
        $translation = $frontPage->translate($locale);
        $translations[$locale] = [
            'title' => old("translations.{$locale}.title", $translation->title ?? ''),
            'content' => old("translations.{$locale}.content", $translation->content ?? ''),
        ];
    }
    @endphp

    <form action="{{ route('admin.front.design.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- 多言語コンテンツエディタ -->
        <x-multilingual-content-editor
            :storageType="old('storage_type', $frontPage->storage_type ?? 'database')"
            :editorType="old('editor_type', $frontPage->editor_type ?? 'html')"
            :translations="$translations"
            :identifier="'front-main-content'"
            :showMetaDescription="false"
            :showOgpImage="false"
        />

        <!-- 保存ボタン -->
        <div class="mt-6 flex justify-end">
            @include('components::form.button', [
                'type' => 'submit',
                'variant' => 'primary',
                'label' => __('common.save'),
                'icon' => 'fas fa-save',
            ])
        </div>
    </form>
</div>
@endsection
