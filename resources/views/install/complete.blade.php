@extends('layouts.install')

@section('title', __('install.complete_title'))
@section('header', __('install.complete_header'))
@section('description', __('install.complete_message'))

@section('content')

    <div class="flex flex-col justify-center items-center space-y-4">
        <!-- ✅ フロントページURL -->
        <div class="flex flex-col justify-center items-center">
            <p class="text-gray-700 font-semibold">{{ __('install.site_url') }}</p>
            <a href="{{ $appUrl }}" class="text-blue-600 underline break-words">{{ $appUrl }}</a>
        </div>

        <!-- ✅ 管理者ログインページURL -->
        
        <div class="flex flex-col justify-center items-center">
            <p class="text-gray-700 font-semibold">{{ __('install.admin_login_url') }}</p>
            <a href="{{ $adminLoginUrl }}" class="text-red-600 underline break-words">{{ $adminLoginUrl }}</a>
        </div>
    </div>

    <div class="flex flex-col items-center justify-center mt-6 space-y-4">
        <!-- ✅ フロントページへのリンク -->
        <div>
            <form method="POST" action="{{ route('install.finalize') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $appUrl }}">
                <button type="submit" class="block w-auto bg-blue-600 text-white py-2 px-4 rounded-lg text-center hover:bg-blue-700 transition">
                    {{ __('install.go_to_site') }}
                </button>
            </form>
        </div>

        <!-- ✅ 管理画面トップへのリンク -->
        <div>
            <form method="POST" action="{{ route('install.finalize') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $adminLoginUrl }}">
                <button type="submit" class="block w-auto bg-green-600 text-white py-2 px-4 rounded-lg text-center hover:bg-green-700 transition">
                    {{ __('install.go_to_admin') }}
                </button>
            </form>
        </div>

    </div>

    <!-- 自動finalize処理 -->
    <script>
        let finalizeExecuted = false;
        
        // ページ離脱時にfinalizeを実行
        function executeFinalizeIfNeeded() {
            if (!finalizeExecuted) {
                finalizeExecuted = true;
                
                // 非同期でfinalizeを実行
                fetch('{{ route('install.finalize') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        redirect_to: null // リダイレクトなし
                    })
                }).catch(error => {
                    console.log('Finalize request failed:', error);
                });
            }
        }
        
        // ページ離脱前にfinalizeを実行
        window.addEventListener('beforeunload', executeFinalizeIfNeeded);
        
        // 1分後に自動でfinalizeを実行
        setTimeout(() => {
            executeFinalizeIfNeeded();
        }, 1 * 60 * 1000); // 1分
        
        // ページが非表示になった時にfinalizeを実行
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                executeFinalizeIfNeeded();
            }
        });
    </script>

@endsection