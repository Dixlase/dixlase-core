@extends('layouts.auth')

@section('title', 'エラー')
@section('icon', 'fas fa-exclamation-triangle')
@section('header', 'エラー')
@section('description', 'リクエストの処理中にエラーが発生しました。')

@section('content')
<div class="text-center">
    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900 mb-6">
        <i class="fas fa-exclamation-triangle text-3xl text-yellow-600 dark:text-yellow-400"></i>
    </div>
    
    <p class="text-gray-700 dark:text-gray-300 mb-6">
        {{ $message ?? 'リクエストの処理中にエラーが発生しました。' }}
    </p>
    
    <p class="text-sm text-gray-500 dark:text-gray-400">
        このウィンドウは閉じても問題ありません。
    </p>
</div>
@endsection
