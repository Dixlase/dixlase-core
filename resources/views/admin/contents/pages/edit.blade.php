@extends('admin::partials.layout')

@section('content')

<!-- Flash message for success or error -->
@include('components::flash_message')

<form action="{{ route('admin.contents.pages.update', ['page' => $page->id] ) }}" method="POST">
    @csrf
    @method('PATCH')

    <!-- id -->
    @include('components::form.hidden', [
        'name' => 'id',
        'value' => $page->id,
    ])

    <!-- フォーム -->
    @include('admin::contents.pages.partials.form', [

    ])

    <!-- 保存ボタンとモーダル -->
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'onclick' => "openModal('confirmationModal')",
        'title' => '保存の確認',
        'label' => 'ページを作成',
        'message' => 'この内容でページーを作成しますか？',
        'confirm_label' => '作成',
        'cancel_label' => '戻る',
    ])

</form>
@endsection
