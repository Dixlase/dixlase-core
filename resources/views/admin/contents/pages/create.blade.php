@extends('admin.partials.layout')

@section('content')

    <form action="{{ route('admin.contents.pages.store') }}" method="POST">
        @csrf

        <!-- Title -->
        <div class="mb-4">
            @include('components.form.label', [
                'for' => 'title',
                'text' => 'admin.features.contents.pages.title',
            ])
            @include('components.form.text', [
                'id' => 'title',
                'name' => 'title',
                'value' => old('title', $page->title ?? ''),
                'required' => true,
                'theme' => $theme
            ])
            @include('components.form.error', [
                'messages' => $errors->get('title')
            ])
        </div>

        <!-- URL Slug -->
        <div class="mb-4">
            @include('components.form.label', [
                'for' => 'slug',
                'text' => 'admin.features.contents.pages.slug',
            ])
            @include('components.form.text', [
                'id' => 'slug',
                'name' => 'slug',
                'value' => old('slug', $page->slug ?? ''),
                'required' => true,
                'theme' => $theme
            ])
            @include('components.form.error', [
                'messages' => $errors->get('slug')
            ])
            <p class="text-sm text-gray-500 mt-1">例: "page1" → URL: https://example.com/page1</p>
        </div>

        <!-- Content -->
        <div class="mb-4">
            @include('components.form.label', [
                'for' => 'content',
                'text' => 'admin.features.contents.pages.content',
            ])
            @include('components.form.textarea', [
                'id' => 'content',
                'name' => 'content',
                'value' => old('content', $page->content ?? ''),
                'required' => true,
                'theme' => $theme
            ])
        </div>

        <!-- 保存ボタンとモーダル -->
        @include('components.form.save', [
            'theme' => $theme,
            'id' => 'confirmationModal',
            'label' => '作成',
            'onclick' => "openModal('confirmationModal')",
            'title' => '作成の確認',
            'message' => '新規ページを作成しますか？',
            'confirm_label' => '作成',
            'cancel_label' => '戻る',
        ])
    </form>
</div>
@endsection
