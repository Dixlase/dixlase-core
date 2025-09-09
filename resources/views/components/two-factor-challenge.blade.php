@props([
    'action',
    'resendAction' => null,
    'title' => __('auth.two_factor.title'),
    'prompt' => __('auth.two_factor.prompt'),
    'submitText' => __('auth.two_factor.submit'),
    'resendText' => __('auth.two_factor.resend'),
    'expireMinutes' => config('app.two_factor.email_code_expire', 10),
    'codeLength' => 6,
    'autoSubmit' => true,
    'showExpireTime' => true,
    'showResend' => true
])

<div class="flex flex-col items-center justify-center text-center">
    <form method="POST" action="{{ $action }}" id="twoFactorForm">
        @csrf

        <p class="mb-4 text-gray-700 dark:text-gray-300">
            {!! nl2br(e($prompt)) !!}
        </p>

        @if($showExpireTime)
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            認証コードは <strong>{{ $expireMinutes }}分間</strong> 有効です。
        </p>
        @endif

        <div id="codeInputs" class="grid gap-2 justify-center mb-4" style="grid-template-columns: repeat({{ $codeLength }}, minmax(0, 1fr));">
            @for ($i = 0; $i < $codeLength; $i++)
                <input type="text" inputmode="numeric" maxlength="1"
                    class="text-center border border-gray-300 dark:border-gray-600 rounded w-12 h-12 text-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring focus:ring-indigo-300"
                    id="code-{{ $i }}"
                    oninput="handleTwoFactorInput(event, {{ $i }})"
                    onkeydown="handleTwoFactorKeyDown(event, {{ $i }})"
                    autocomplete="one-time-code">
            @endfor
        </div>

        <input type="hidden" name="code" id="fullCode">

        <button type="submit"
            class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            {{ $submitText }}
        </button>
    </form>

    @if($showResend && $resendAction)
    <form method="POST" action="{{ $resendAction }}">
        @csrf
        <button type="submit"
            class="text-sm text-indigo-600 hover:underline dark:text-indigo-400 mt-4 block">
            {{ $resendText }}
        </button>
    </form>
    @endif

    <script>
        const twoFactorConfig = {
            codeLength: {{ $codeLength }},
            autoSubmit: {{ $autoSubmit ? 'true' : 'false' }}
        };

        function handleTwoFactorInput(e, index) {
            const input = e.target;
            const value = input.value;

            if (!/^[0-9]$/.test(value)) {
                input.value = '';
                return;
            }

            // フォーカスを次に移動
            if (index < twoFactorConfig.codeLength - 1 && value) {
                document.getElementById(`code-${index + 1}`).focus();
            }

            // 自動送信が有効で全部入力されてたら自動送信
            if (twoFactorConfig.autoSubmit) {
                let code = '';
                for (let i = 0; i < twoFactorConfig.codeLength; i++) {
                    const val = document.getElementById(`code-${i}`).value;
                    if (!val) return;
                    code += val;
                }

                document.getElementById('fullCode').value = code;
                document.getElementById('twoFactorForm').submit();
            }
        }

        function handleTwoFactorKeyDown(e, index) {
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
            if (pasted.length !== twoFactorConfig.codeLength) return;

            for (let i = 0; i < twoFactorConfig.codeLength; i++) {
                document.getElementById(`code-${i}`).value = pasted[i];
            }

            if (twoFactorConfig.autoSubmit) {
                document.getElementById('fullCode').value = pasted;
                document.getElementById('twoFactorForm').submit();
            }
        });
    </script>
</div>
