@extends('themes::layouts.app')

@section('title', ' - Page Not Found')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">
        {{-- 404 Icon --}}
        <div class="mb-8">
            <svg class="mx-auto h-32 w-32 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        {{-- Error Message --}}
        <h1 class="text-6xl font-bold text-gray-900 dark:text-white mb-4">404</h1>
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-4">
            ページが見つかりません
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            お探しのページは存在しないか、移動した可能性があります。
        </p>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <x-front.button variant="primary" href="{{ url('/') }}">
                ホームに戻る
            </x-front.button>
            <x-front.button variant="outline" onclick="history.back()">
                前のページに戻る
            </x-front.button>
        </div>
    </div>
</div>
@endsection
