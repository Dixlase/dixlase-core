@extends('admin::partials.layout-auth')

@section('title', '2段階認証コード入力')

@section('content')
    <div class="flex flex-col items-center justify-center text-center">
        <form method="POST" action="{{ route('admin.two-factor.confirm') }}" id="twoFactorForm">
            @csrf

            <p class="mb-4 text-gray-700 dark:text-gray-300">
                {!! nl2br(e(__('auth.two_factor.prompt'))) !!}
            </p>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                認証コードは <strong>{{ config('app.two_factor.email_code_expire') }}分間</strong> 有効です。
            </p>

            <div id="codeInputs" class="grid grid-cols-6 gap-2 justify-center mb-4">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" inputmode="numeric" maxlength="1"
                        class="text-center border border-gray-300 dark:border-gray-600 rounded w-12 h-12 text-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring focus:ring-indigo-300"
                        id="code-{{ $i }}"
                        oninput="handleInput(event, {{ $i }})"
                        onkeydown="handleKeyDown(event, {{ $i }})"
                        autocomplete="one-time-code">
                @endfor
            </div>

            <input type="hidden" name="code" id="fullCode">

            <button type="submit"
                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                {{ __('auth.two_factor.submit') }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.two-factor.resend') }}">
            @csrf
            <button type="submit"
                class="text-sm text-indigo-600 hover:underline dark:text-indigo-400 mt-4 block">
                {{ __('auth.two_factor.resend') }}
            </button>
        </form>

        <script>
            function handleInput(e, index) {
                const input = e.target;
                const value = input.value;

                if (!/^[0-9]$/.test(value)) {
                    input.value = '';
                    return;
                }

                // フォーカスを次に移動
                if (index < 5 && value) {
                    document.getElementById(`code-${index + 1}`).focus();
                }

                // 全部入力されてたら自動送信
                let code = '';
                for (let i = 0; i < 6; i++) {
                    const val = document.getElementById(`code-${i}`).value;
                    if (!val) return;
                    code += val;
                }

                document.getElementById('fullCode').value = code;
                document.getElementById('twoFactorForm').submit();
            }

            function handleKeyDown(e, index) {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    document.getElementById(`code-${index - 1}`).focus();
                }
            }

            // 最初のフィールドに自動フォーカス
            window.addEventListener('DOMContentLoaded', () => {
                document.getElementById('code-0').focus();
            });

            // コピペ対応（1つ目にペーストされたら全体に分配）
            document.getElementById('code-0').addEventListener('paste', function (e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (pasted.length !== 6) return;

                for (let i = 0; i < 6; i++) {
                    document.getElementById(`code-${i}`).value = pasted[i];
                }

                document.getElementById('fullCode').value = pasted;
                document.getElementById('twoFactorForm').submit();
            });
        </script>
    </div>
@endsection