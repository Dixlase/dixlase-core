@extends('layouts.app')

@section('content')
<div class="frontpage-builder">
    {{-- フロントページビルダーコンテンツがここに動的に挿入されます --}}
    @yield('builder-content')
    
    {{-- ビルダーコンテンツがない場合のデフォルト表示 --}}
    @if(!View::hasSection('builder-content'))
        <div class="container mx-auto px-4 py-16">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Welcome to {{ config('app.name', 'Dixlase') }}
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-400 mb-8">
                    フロントページビルダーでこのページをカスタマイズできます
                </p>
                <x-front.button variant="primary" size="lg" href="/admin">
                    管理画面へ
                </x-front.button>
            </div>
        </div>
    @endif
</div>
@endsection
