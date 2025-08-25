{{--
    メールサーバー設定フォーム共通コンポーネント
    
    @param array $settings - メール設定値
    @param array $mailers - メーラー選択肢 (オプション)
    @param array $encryptions - 暗号化選択肢 (オプション)
    @param string $context - 'install' または 'admin' (デフォルト: 'admin')
    @param string $admin_email - 管理者メールアドレス (インストール時のみ)
--}}

@php
    $context = $context ?? 'admin';
    $isInstall = $context === 'install';
    
    // デフォルト値の設定
    $defaultMailers = [
        'smtp' => 'SMTP',
        'sendmail' => 'Sendmail', 
        'log' => 'Log'
    ];
    
    $defaultEncryptions = [
        '' => 'None',
        'tls' => 'TLS',
        'ssl' => 'SSL'
    ];
    
    $mailers = $mailers ?? $defaultMailers;
    $encryptions = $encryptions ?? $defaultEncryptions;
@endphp

@if($isInstall)
<script>
document.addEventListener('DOMContentLoaded', function() {
    // メール設定の入力フィールドを監視
    const mailInputs = document.querySelectorAll('.mail-setting-input');
    
    mailInputs.forEach(input => {
        input.addEventListener('change', function() {
            resetMailTestResults();
        });
        
        input.addEventListener('input', function() {
            resetMailTestResults();
        });
    });
    
    function resetMailTestResults() {
        // セッションをリセット（サーバーサイドでの処理が必要）
        fetch('{{ route("install.mail.reset-tests") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // まずテキストとして取得
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Mail tests reset response:', data);
                console.log('Session reset debug info:', data.debug);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
            }
        })
        .catch(error => {
            console.error('Error resetting mail tests:', error);
        });
        // エラーが発生してもUIはリセットする
        resetTestStatusUI();
    }
    
    function resetTestStatusUI() {
        // メインステータスをリセット
        const mainStatusDiv = document.getElementById('mail-test-status');
        const mainIcon = document.getElementById('status-icon');
        const mainTitle = document.getElementById('status-title');
        
        if (mainStatusDiv && mainIcon && mainTitle) {
            mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
            
            // SVGアイコンの場合はsetAttributeを使用
            if (mainIcon.tagName === 'svg' || mainIcon.classList.contains('svg-inline--fa')) {
                mainIcon.setAttribute('class', 'fas fa-exclamation-triangle text-yellow-400 text-xl');
            } else {
                mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
            }
            
            mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
            mainTitle.textContent = 'メールテストが未完了です';
        }
        
        // JavaScript変数もリセット
        if (typeof connectionTested !== 'undefined') {
            connectionTested = false;
        }
        if (typeof sendTested !== 'undefined') {
            sendTested = false;
        }
        if (typeof receiveTested !== 'undefined') {
            receiveTested = false;
        }
        
        // 個別テストアイコンをリセット
        resetTestIcon('connection');
        resetTestIcon('send');
        resetTestIcon('receive');
        
        // メインステータスを強制的に更新（mail-test.blade.phpの関数を呼び出し）
        console.log('=== resetTestStatusUI デバッグ ===');
        console.log('updateInstallMainStatus関数の存在:', typeof updateInstallMainStatus);
        if (typeof updateInstallMainStatus === 'function') {
            console.log('updateInstallMainStatus関数を呼び出し中...');
            updateInstallMainStatus();
            console.log('updateInstallMainStatus関数呼び出し完了');
        } else {
            console.log('updateInstallMainStatus関数が見つかりません');
        }
        
        // テストボタンを無効化
        const connectionBtn = document.getElementById('test-connection-btn');
        const mailBtn = document.getElementById('test-mail-btn');
        
        if (connectionBtn) {
            connectionBtn.disabled = false;
        }
        
        if (mailBtn) {
            mailBtn.disabled = true;
            mailBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-gray-400 text-white cursor-not-allowed';
        }
    }
    
    function resetTestIcon(testType) {
        const icon = document.getElementById(testType + '-test-icon');
        const text = document.getElementById(testType + '-test-text');
        const dateSpan = document.getElementById(testType + '-test-date');
        
        if (icon) {
            icon.className = 'mr-2 fas fa-times-circle text-gray-400';
        }
        
        if (text) {
            text.className = 'text-sm text-gray-600 dark:text-gray-400';
        }
        
        if (dateSpan) {
            dateSpan.textContent = '';
        }
    }
});
</script>
@endif

<!-- Mailer -->
<div class="{{ $isInstall ? '' : 'mt-4' }}">
    @if($isInstall)
        <label for="mail_mailer" class="block text-gray-700">{{ __($context . '.mail_mailer') }}</label>
        <select name="mail_mailer" id="mail_mailer" class="w-full p-2 border rounded-lg mail-setting-input">
            @foreach($mailers as $value => $label)
                <option value="{{ $value }}" {{ old('mail_mailer', session('install_data.mail_mailer', 'smtp')) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    @else
        @include('components::form.label', [
            'for' => 'mail_mailer',
            'text' => __('admin.settings.base.mailer'),
        ])
        @include('components::form.select', [
            'id' => 'mail_mailer',
            'name' => 'mail_mailer',
            'options' => $mailers,
            'value' => old('mail_mailer', $settings['mail_mailer']),
        ])
    @endif
</div>

<!-- Host -->
<div class="{{ $isInstall ? '' : 'mt-4' }}">
    @if($isInstall)
        <label for="mail_host" class="block text-gray-700">{{ __($context . '.mail_host') }}</label>
        <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', session('install_data.mail_host', 'mailpit')) }}" class="w-full p-2 border rounded-lg mail-setting-input">
    @else
        @include('components::form.label', [
            'for' => 'mail_host',
            'text' => __('admin.settings.base.mail_host'),
        ])
        @include('components::form.text', [
            'id' => 'mail_host',
            'name' => 'mail_host',
            'value' => old('mail_host', $settings['mail_host']),
        ])
    @endif
</div>

<!-- Port -->
<div class="{{ $isInstall ? '' : 'mt-4' }}">
    @if($isInstall)
        <label for="mail_port" class="block text-gray-700">{{ __($context . '.mail_port') }}</label>
        <input type="number" name="mail_port" id="mail_port" value="{{ old('mail_port', session('install_data.mail_port', '1025')) }}" class="w-full p-2 border rounded-lg mail-setting-input">
    @else
        @include('components::form.label', [
            'for' => 'mail_port',
            'text' => __('admin.settings.base.mail_port'),
        ])
        @include('components::form.text', [
            'id' => 'mail_port',
            'name' => 'mail_port',
            'value' => old('mail_port', $settings['mail_port']),
        ])
    @endif
</div>

<!-- Username -->
<div class="{{ $isInstall ? '' : 'mt-4' }}">
    @if($isInstall)
        <label for="mail_username" class="block text-gray-700">{{ __($context . '.mail_username') }}</label>
        <input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username', session('install_data.mail_username')) }}" class="w-full p-2 border rounded-lg mail-setting-input">
    @else
        @include('components::form.label', [
            'for' => 'mail_username',
            'text' => __('admin.settings.base.mail_username'),
        ])
        @include('components::form.text', [
            'id' => 'mail_username',
            'name' => 'mail_username',
            'value' => old('mail_username', $settings['mail_username']),
        ])
    @endif
</div>

<!-- Password -->
<div class="{{ $isInstall ? '' : 'mt-4' }}">
    @if($isInstall)
        <label for="mail_password" class="block text-gray-700">{{ __($context . '.mail_password') }}</label>
        <input type="password" name="mail_password" id="mail_password" value="{{ old('mail_password') }}" class="w-full p-2 border rounded-lg mail-setting-input">
    @else
        @include('components::form.label', [
            'for' => 'mail_password',
            'text' => __('admin.settings.base.mail_password'),
        ])
        @include('components::form.text', [
            'id' => 'mail_password',
            'name' => 'mail_password',
            'value' => old('mail_password', $settings['mail_password']),
        ])
    @endif
</div>

<!-- Encryption -->
<div class="{{ $isInstall ? '' : 'mt-4' }}">
    @if($isInstall)
        <label for="mail_encryption" class="block text-gray-700">{{ __($context . '.mail_encryption') }}</label>
        <select name="mail_encryption" id="mail_encryption" class="w-full p-2 border rounded-lg mail-setting-input">
            @foreach($encryptions as $value => $label)
                <option value="{{ $value }}" {{ old('mail_encryption', session('install_data.mail_encryption')) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    @else
        @include('components::form.label', [
            'for' => 'mail_encryption',
            'text' => __('admin.settings.base.mail_encryption'),
        ])
        @include('components::form.select', [
            'id' => 'mail_encryption',
            'name' => 'mail_encryption',
            'options' => $encryptions,
            'value' => old('mail_encryption', $settings['mail_encryption']),
        ])
    @endif
</div>

<!-- From Address -->
<div class="{{ $isInstall ? '' : 'mt-4' }}">
    @if($isInstall)
        <label for="mail_from_address" class="block text-gray-700">{{ __($context . '.mail_from_address') }}</label>
        <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', session('install_data.mail_from_address', $admin_email ?? '')) }}" class="w-full p-2 border rounded-lg mail-setting-input">
    @else
        @include('components::form.label', [
            'for' => 'mail_from_address',
            'text' => __('admin.settings.base.mail_from_address'),
        ])
        @include('components::form.text', [
            'id' => 'mail_from_address',
            'name' => 'mail_from_address',
            'value' => old('mail_from_address', $settings['mail_from_address']),
        ])
    @endif
</div>
