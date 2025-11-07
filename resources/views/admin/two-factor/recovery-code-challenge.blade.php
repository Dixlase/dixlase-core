@extends('layouts.auth')

@section('title', __('two-factor.recovery_code.title'))
@section('icon')
["fas fa-life-ring", "fas fa-key"]
@endsection
@section('header', __('two-factor.recovery_code.title'))
@section('description', __('two-factor.recovery_code.prompt'))

@section('content')
    <form method="POST" action="{{ route('admin.two-factor.recovery-code.confirm') }}" class="space-y-6" id="recoveryCodeForm">
        @csrf

        <!-- 回復コード入力 -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('two-factor.recovery_code.code_label') }}
            </label>
            
            <!-- 5桁×4ブロックの入力フィールド -->
            <div class="flex items-center justify-center gap-2">
                <input
                    type="text"
                    id="code1"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="12345"
                    required
                    autofocus
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code2"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="67890"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code3"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="12345"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code4"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="67890"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
            </div>

            <!-- 隠しフィールド（実際に送信される値） -->
            <input type="hidden" name="recovery_code" id="recovery_code">

            @error('recovery_code')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400 text-center">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                {{ __('two-factor.recovery_code.format_hint') }}
            </p>
        </div>

        <!-- 送信ボタン -->
        <div>
            <button
                type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600"
            >
                {{ __('two-factor.recovery_code.submit') }}
            </button>
        </div>
    </form>

    <!-- 別の認証方法に戻る -->
    <div class="mt-6 text-center">
        <a href="{{ route('admin.two-factor.email.show') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
            {{ __('two-factor.recovery_code.back_to_2fa') }}
        </a>
    </div>
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two-factor.back_to_login') }}
    </a>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['code1', 'code2', 'code3', 'code4'];
    const form = document.getElementById('recoveryCodeForm');
    
    // 各入力フィールドにイベントリスナーを設定
    inputs.forEach((id, index) => {
        const input = document.getElementById(id);
        
        // 入力時の処理
        input.addEventListener('input', function(e) {
            // 数字のみ許可
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // 5桁入力したら次のフィールドに移動
            if (this.value.length === 5 && index < inputs.length - 1) {
                document.getElementById(inputs[index + 1]).focus();
            }
        });
        
        // キー入力時の処理
        input.addEventListener('keydown', function(e) {
            // Backspaceで前のフィールドに戻る
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                document.getElementById(inputs[index - 1]).focus();
            }
            
            // 左矢印キーで前のフィールドに移動
            if (e.key === 'ArrowLeft' && this.selectionStart === 0 && index > 0) {
                const prevInput = document.getElementById(inputs[index - 1]);
                prevInput.focus();
                prevInput.setSelectionRange(prevInput.value.length, prevInput.value.length);
            }
            
            // 右矢印キーで次のフィールドに移動
            if (e.key === 'ArrowRight' && this.selectionStart === this.value.length && index < inputs.length - 1) {
                const nextInput = document.getElementById(inputs[index + 1]);
                nextInput.focus();
                nextInput.setSelectionRange(0, 0);
            }
        });
        
        // ペースト処理
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
            
            if (pastedData.length >= 20) {
                // 20桁以上の場合、各フィールドに5桁ずつ分配
                document.getElementById('code1').value = pastedData.substring(0, 5);
                document.getElementById('code2').value = pastedData.substring(5, 10);
                document.getElementById('code3').value = pastedData.substring(10, 15);
                document.getElementById('code4').value = pastedData.substring(15, 20);
                document.getElementById('code4').focus();
            } else {
                // 短い場合は現在のフィールドから順に入力
                let remaining = pastedData;
                for (let i = index; i < inputs.length && remaining.length > 0; i++) {
                    const field = document.getElementById(inputs[i]);
                    const chunk = remaining.substring(0, 5);
                    field.value = chunk;
                    remaining = remaining.substring(5);
                    if (remaining.length > 0 && i < inputs.length - 1) {
                        document.getElementById(inputs[i + 1]).focus();
                    }
                }
            }
        });
    });
    
    // フォーム送信時に隠しフィールドに値を結合
    form.addEventListener('submit', function(e) {
        const code1 = document.getElementById('code1').value;
        const code2 = document.getElementById('code2').value;
        const code3 = document.getElementById('code3').value;
        const code4 = document.getElementById('code4').value;
        
        // 全て5桁入力されているかチェック
        if (code1.length !== 5 || code2.length !== 5 || code3.length !== 5 || code4.length !== 5) {
            e.preventDefault();
            alert('{{ __("two-factor.recovery_code.format_hint") }}');
            return false;
        }
        
        // 隠しフィールドに結合した値を設定
        document.getElementById('recovery_code').value = code1 + code2 + code3 + code4;
    });
});
</script>
@endpush
