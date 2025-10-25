@extends('layouts.auth')

@section('title', 'ログイン承認完了')
@section('icon', 'fas fa-check-circle')
@section('header', 'ログイン承認完了')
@section('description', 'ログインが承認されました。')

@section('content')
<div class="text-center">
    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900 mb-6">
        <i class="fas fa-check text-3xl text-green-600 dark:text-green-400"></i>
    </div>
    
    <p class="text-gray-700 dark:text-gray-300 mb-6">
        ログインリクエストが承認されました。<br>
        ログイン画面に戻って、自動的にログインが完了します。
    </p>
    
    <p class="text-sm text-gray-500 dark:text-gray-400">
        このウィンドウは閉じても問題ありません。
    </p>
</div>
@endsection
