{{--
    メールテスト機能共通コンポーネント
    
    @param string $context - 'install' または 'admin' (デフォルト: 'admin')
    @param string $connectionTestRoute - 接続テスト用ルート
    @param string $mailTestRoute - メール送信テスト用ルート
    @param bool $showStatus - テスト状態表示を含めるか (デフォルト: false、管理画面用)
    @param array $testStatus - テスト状態データ (管理画面用)
--}}

@php
    $context = $context ?? 'admin';
    $isInstall = $context === 'install';
    $showStatus = $showStatus ?? false;
@endphp

@if($showStatus && !$isInstall)
    <!-- メール機能テスト状態の表示 (管理画面用) -->
    <div class="mt-6 p-4 border rounded-lg 
        @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
            bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800
        @else
            bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800
        @endif
    ">
        <div class="flex items-start mb-4">
            <div class="flex-shrink-0">
                @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                @else
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                @endif
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium 
                    @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
                        text-green-800 dark:text-green-200
                    @else
                        text-yellow-800 dark:text-yellow-200
                    @endif
                ">
                    @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
                        {{ __('admin.settings.base.view_messages.mail_test_complete') }}
                    @else
                        {{ __('admin.settings.base.view_messages.mail_test_incomplete') }}
                    @endif
                </h3>
            </div>
        </div>

        <!-- テスト進捗状況 -->
        <div class="space-y-2">
            <!-- 接続テスト -->
            <div id="connection-test-status" class="flex items-center">
                <i id="connection-test-icon" class="mr-2 {{ $testStatus['connection_tested'] ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                <span id="connection-test-text" class="text-sm {{ $testStatus['connection_tested'] ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                    1. {{ __('admin.settings.base.view_messages.connection_test') }}
                    <span id="connection-test-date">
                        @if($testStatus['connection_tested'] && $testStatus['connection_test_date'])
                            ({{ $testStatus['connection_test_date'] }})
                        @endif
                    </span>
                </span>
            </div>

            <!-- 送信テスト -->
            <div id="send-test-status" class="flex items-center">
                <i id="send-test-icon" class="mr-2 {{ $testStatus['send_tested'] ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                <span id="send-test-text" class="text-sm {{ $testStatus['send_tested'] ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                    2. {{ __('admin.settings.base.view_messages.send_test') }}
                    <span id="send-test-date">
                        @if($testStatus['send_tested'] && $testStatus['send_test_date'])
                            ({{ $testStatus['send_test_date'] }})
                        @endif
                    </span>
                </span>
            </div>

            <!-- 受信確認テスト -->
            <div id="receive-test-status" class="flex items-center">
                <i id="receive-test-icon-main" class="mr-2 {{ $testStatus['receive_tested'] ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                <span id="receive-test-text-main" class="text-sm {{ $testStatus['receive_tested'] ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                    3. {{ __('admin.settings.base.view_messages.receive_test') }}
                    <span id="receive-test-date-main">
                        @if($testStatus['receive_tested'] && $testStatus['receive_test_date'])
                            ({{ $testStatus['receive_test_date'] }})
                        @endif
                    </span>
                </span>
            </div>
        </div>
    </div>
@endif

<!-- メールテスト機能 -->
<div class="mt-6 p-4 {{ $isInstall ? 'bg-gray-50 border border-gray-200' : 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700' }} rounded-lg">
    <h3 class="text-lg font-medium {{ $isInstall ? 'text-gray-900' : 'text-gray-900 dark:text-gray-100' }} mb-2">
        {{ __('mail.test.title') }}
    </h3>
    <p class="text-sm {{ $isInstall ? 'text-gray-600' : 'text-gray-600 dark:text-gray-400' }} mb-4">
        {{ __('mail.settings.mail_test_description') }}<br>
        @if($isInstall)
            {{ __('mail.test.description_admin_email') }}
        @else
            {{ __('mail.settings.mail_test_description_2') }}
        @endif
    </p>
    <div class="flex space-x-3">
        <button type="button" id="test-connection-btn" class="bg-green-500 hover:bg-green-600 {{ $isInstall ? '' : 'dark:bg-green-600 dark:hover:bg-green-700' }} text-white font-bold py-2 px-4 rounded transition-colors duration-200">
            {{ __('mail.settings.test_connection_button') }}
        </button>
        <button 
            type="button"
            id="test-mail-btn" 
            class="
                px-4 py-2 rounded-lg font-medium transition-colors duration-200
                @if(($isInstall && $testStatus['connection_tested']) || (!$isInstall && (!$showStatus || $testStatus['connection_tested'])))
                    bg-blue-500 hover:bg-blue-600 {{ $isInstall ? '' : 'dark:bg-blue-600 dark:hover:bg-blue-700' }} text-white
                @else
                    bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed
                @endif
            "
            @if(($isInstall && !$testStatus['connection_tested']) || (!$isInstall && $showStatus && !$testStatus['connection_tested'])) disabled @endif
        >
            {{ __('mail.settings.test_mail_button') }}
        </button>
    </div>
    <div id="test-result" class="hidden mt-4"></div>

    @if($context === 'install')
    <!-- メール受信確認ステータス（インストール用） -->
    <div id="mail-test-status" class="mt-6 p-4 border rounded-lg @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested']) bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 @else bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 @endif">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i id="status-icon" class="@if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested']) fas fa-check-circle text-green-400 @else fas fa-exclamation-triangle text-yellow-400 @endif text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 id="status-title" class="text-sm font-medium @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested']) text-green-800 dark:text-green-200 @else text-yellow-800 dark:text-yellow-200 @endif">
                    @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
                        {{ __('mail.test.three_stage_test_complete') }}
                    @else
                        {{ __('mail.test.three_stage_test_incomplete') }}
                    @endif
                </h3>
                <div class="mt-3 text-sm text-yellow-700 dark:text-yellow-300">
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i id="connection-test-icon" class="mr-2 fas @if($testStatus['connection_tested']) fa-circle-check text-green-600 @else fa-times-circle text-gray-400 @endif"></i>
                            <span id="connection-test-text" class="text-sm @if($testStatus['connection_tested']) text-green-700 dark:text-green-300 @else text-gray-600 dark:text-gray-400 @endif">
                                {{ __('mail.test.connection_test') }}
                            </span>
                            <span id="connection-test-date" class="text-xs text-gray-500">@if($testStatus['connection_tested']) ({{ $testStatus['connection_test_date'] }}) @endif</span>
                        </li>
                        <li class="flex items-center">
                            <i id="send-test-icon" class="mr-2 fas @if($testStatus['send_tested']) fa-circle-check text-green-600 @else fa-times-circle text-gray-400 @endif"></i>
                            <span id="send-test-text" class="text-sm @if($testStatus['send_tested']) text-green-700 dark:text-green-300 @else text-gray-600 dark:text-gray-400 @endif">
                                {{ __('mail.test.send_test') }}
                            </span>
                            <span id="send-test-date" class="text-xs text-gray-500">@if($testStatus['send_tested']) ({{ $testStatus['send_test_date'] }}) @endif</span>
                        </li>
                        <li class="flex items-center">
                            <i id="receive-test-icon" class="mr-2 fas @if($testStatus['receive_tested']) fa-circle-check text-green-600 @else fa-circle-xmark text-gray-400 @endif"></i>
                            <span id="receive-test-text" class="text-sm @if($testStatus['receive_tested']) text-green-700 dark:text-green-300 @else text-gray-600 dark:text-gray-400 @endif">
                                {{ __('mail.test.receive_test') }}
                            </span>
                            <span id="receive-test-date" class="text-xs text-gray-500">@if($testStatus['receive_tested']) ({{ $testStatus['receive_test_date'] }}) @endif</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - mail-test.blade.php');
    
    // 接続テストボタンのイベントリスナー
    const connectionBtn = document.getElementById('test-connection-btn');
    if (connectionBtn) {
        console.log('接続テストボタンが見つかりました');
        connectionBtn.addEventListener('click', function() {
            console.log('接続テストボタンがクリックされました');
            testConnection();
        });
    } else {
        console.log('接続テストボタンが見つかりません');
    }

    // メール送信テストボタンのイベントリスナー
    const mailBtn = document.getElementById('test-mail-btn');
    if (mailBtn) {
        console.log('メール送信テストボタンが見つかりました');
        mailBtn.addEventListener('click', function() {
            console.log('メール送信テストボタンがクリックされました');
            testMail();
        });
    } else {
        console.log('メール送信テストボタンが見つかりません');
    }

    // 接続テスト関数
    function testConnection() {
        console.log('testConnection関数が呼び出されました');
        
        const connectionTestRoute = '{{ $connectionTestRoute ?? "" }}';
        console.log('接続テストルート:', connectionTestRoute);
        
        if (!connectionTestRoute) {
            console.error('接続テストルートが設定されていません');
            showTestResult('error', 'テストルートが設定されていません');
            return;
        }

        // ボタンを無効化
        const btn = document.getElementById('test-connection-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'テスト中...';
        }

        // CSRF トークンを取得
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('CSRFトークン:', csrfToken ? 'あり' : 'なし');
        
        // フォームからメール設定を取得
        const mailSettings = {
            mail_mailer: document.querySelector('select[name="mail_mailer"]')?.value || '',
            mail_host: document.querySelector('input[name="mail_host"]')?.value || '',
            mail_port: document.querySelector('input[name="mail_port"]')?.value || '',
            mail_username: document.querySelector('input[name="mail_username"]')?.value || '',
            mail_password: document.querySelector('input[name="mail_password"]')?.value || '',
            mail_encryption: document.querySelector('select[name="mail_encryption"]')?.value || '',
            mail_from_address: document.querySelector('input[name="mail_from_address"]')?.value || ''
        };

        console.log('接続テスト開始:', connectionTestRoute);
        console.log('送信するメール設定:', mailSettings);

        fetch(connectionTestRoute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(mailSettings)
        })
        .then(response => {
            console.log('接続テストレスポンス受信:', response.status);
            if (!response.ok) {
                // エラーレスポンスの詳細を取得
                return response.json().then(errorData => {
                    console.error('バリデーションエラー詳細:', errorData);
                    throw new Error(`HTTP ${response.status}: ${response.statusText} - ${JSON.stringify(errorData)}`);
                }).catch(() => {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('接続テスト結果:', data);
            
            if (data.success) {
                updateTestStatus('connection', true, data.test_date);
                enableMailTestButton();
                showTestResult('success', data.message || '接続テストが成功しました');
            } else {
                showTestResult('error', data.message || '接続テストが失敗しました');
            }
        })
        .catch(error => {
            console.error('接続テストエラー:', error);
            showTestResult('error', '接続テストでエラーが発生しました: ' + error.message);
        })
        .finally(() => {
            // ボタンを再有効化
            if (btn) {
                btn.disabled = false;
                btn.textContent = '{{ __("mail.settings.test_connection_button") }}';
            }
        });
    }

    // メール送信テスト関数
    function testMail() {
        console.log('testMail関数が呼び出されました');
        
        const mailTestRoute = '{{ $mailTestRoute ?? "" }}';
        if (!mailTestRoute) {
            console.error('メールテストルートが設定されていません');
            showTestResult('error', 'メールテストルートが設定されていません');
            return;
        }

        // 接続テストが完了しているかチェック
        const isInstall = {{ $isInstall ? 'true' : 'false' }};
        if (isInstall) {
            const installData = JSON.parse(sessionStorage.getItem('install_data') || '{}');
            if (!installData.mail_connection_tested) {
                showTestResult('error', '先に接続テストを実行してください');
                return;
            }
        }

        // ボタンを無効化
        const btn = document.getElementById('test-mail-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'テスト中...';
        }

        // CSRF トークンを取得
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // フォームからメール設定を取得
        const mailSettings = {
            mail_mailer: document.querySelector('select[name="mail_mailer"]')?.value || '',
            mail_host: document.querySelector('input[name="mail_host"]')?.value || '',
            mail_port: document.querySelector('input[name="mail_port"]')?.value || '',
            mail_username: document.querySelector('input[name="mail_username"]')?.value || '',
            mail_password: document.querySelector('input[name="mail_password"]')?.value || '',
            mail_encryption: document.querySelector('select[name="mail_encryption"]')?.value || '',
            mail_from_address: document.querySelector('input[name="mail_from_address"]')?.value || '',
            mail_from_name: document.querySelector('input[name="mail_from_name"]')?.value || ''
        };

        console.log('メール送信テスト開始:', mailTestRoute);
        console.log('送信するメール設定:', mailSettings);

        fetch(mailTestRoute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(mailSettings)
        })
        .then(response => {
            console.log('メール送信テストレスポンス受信:', response.status);
            if (!response.ok) {
                // エラーレスポンスの詳細を取得
                return response.json().then(errorData => {
                    console.error('バリデーションエラー詳細:', errorData);
                    throw new Error(`HTTP ${response.status}: ${response.statusText} - ${JSON.stringify(errorData)}`);
                }).catch(() => {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('メール送信テスト結果:', data);
            
            if (data.success) {
                updateTestStatus('send', true, data.test_date);
                showTestResult('success', data.message || 'メール送信テストが成功しました');
            } else {
                showTestResult('error', data.message || 'メール送信テストが失敗しました');
            }
        })
        .catch(error => {
            console.error('メール送信テストエラー:', error);
            showTestResult('error', 'メール送信テストでエラーが発生しました: ' + error.message);
        })
        .finally(() => {
            // ボタンを再有効化
            if (btn) {
                btn.disabled = false;
                btn.textContent = '{{ __("mail.settings.test_mail_button") }}';
            }
        });
    }

    // テスト結果表示関数
    function showTestResult(type, message) {
        const resultDiv = document.getElementById('test-result');
        if (!resultDiv) {
            console.log('test-result要素が見つかりません');
            return;
        }

        resultDiv.className = `mt-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'}`;
        resultDiv.textContent = message;
        resultDiv.classList.remove('hidden');

        // 5秒後に非表示
        setTimeout(() => {
            resultDiv.classList.add('hidden');
        }, 5000);
    }

    // テストステータス更新関数（グローバルスコープに定義）
    function updateTestStatus(testType, success, testDate) {
        console.log(`updateTestStatus呼び出し: ${testType}, success: ${success}, date: ${testDate}`);
        
        // 両方のDOM要素を更新（メイン表示とステータス表示）
        const icon = document.getElementById(testType + '-test-icon');
        const text = document.getElementById(testType + '-test-text');
        const dateSpan = document.getElementById(testType + '-test-date');
        
        // メイン表示の要素も取得
        const iconMain = document.getElementById(testType + '-test-icon-main');
        const textMain = document.getElementById(testType + '-test-text-main');
        const dateSpanMain = document.getElementById(testType + '-test-date-main');
        
        console.log('アイコン要素:', icon);
        console.log('テキスト要素:', text);
        console.log('日付要素:', dateSpan);
        console.log('メインアイコン要素:', iconMain);
        console.log('メインテキスト要素:', textMain);
        console.log('メイン日付要素:', dateSpanMain);
        
        // DOM要素が見つからない場合の詳細デバッグ（開発時のみ）
        if (!icon && !iconMain) console.warn(`両方のアイコン要素が見つかりません: ${testType}`);
        if (!text && !textMain) console.warn(`両方のテキスト要素が見つかりません: ${testType}`);
        if (!dateSpan && !dateSpanMain) console.warn(`両方の日付要素が見つかりません: ${testType}`);
        
        if (success) {
            // ステータス表示の要素を更新
            if (icon) {
                console.log('アイコンを成功状態に更新');
                icon.className = 'mr-2 fas fa-circle-check text-green-600';
            }
            
            if (text) {
                console.log('テキストを成功状態に更新');
                text.className = 'text-sm text-green-700 dark:text-green-300';
            }
            
            if (dateSpan && testDate) {
                console.log('日付を更新:', testDate);
                dateSpan.textContent = `(${testDate})`;
            }
            
            // メイン表示の要素も更新
            if (iconMain) {
                console.log('メインアイコンを成功状態に更新');
                iconMain.className = 'mr-2 fas fa-check-circle text-green-500';
            }
            
            if (textMain) {
                console.log('メインテキストを成功状態に更新');
                textMain.className = 'text-sm text-green-700 dark:text-green-300';
            }
            
            if (dateSpanMain && testDate) {
                console.log('メイン日付を更新:', testDate);
                dateSpanMain.textContent = `(${testDate})`;
            }
            
            // セッションストレージに保存（インストール時）
            const isInstall = {{ $isInstall ? 'true' : 'false' }};
            if (isInstall) {
                const installData = JSON.parse(sessionStorage.getItem('install_data') || '{}');
                installData[`mail_${testType}_tested`] = true;
                installData[`mail_${testType}_test_date`] = testDate;
                sessionStorage.setItem('install_data', JSON.stringify(installData));
                console.log('セッションストレージ更新:', installData);
            }
        }
        
        // メインステータス更新
        updateInstallMainStatus();
    }
    
    // グローバルスコープに関数を公開
    window.updateTestStatus = updateTestStatus;
    window.showNotification = showNotification;

    // メインステータス更新関数
    function updateInstallMainStatus() {
        console.log('updateInstallMainStatus関数呼び出し');
        
        const mainStatusDiv = document.querySelector('.mt-6.p-4.border.rounded-lg');
        const mainIcon = document.getElementById('status-icon');
        const mainTitle = document.getElementById('status-title');
        
        console.log('メインステータス要素:', { mainStatusDiv, mainIcon, mainTitle });
        
        // テスト完了状態をチェック
        const isInstall = {{ $isInstall ? 'true' : 'false' }};
        let allTestsComplete = false;
        
        if (isInstall) {
            const installData = JSON.parse(sessionStorage.getItem('install_data') || '{}');
            allTestsComplete = installData.mail_connection_tested && installData.mail_send_tested && installData.mail_receive_tested;
            console.log('インストール時のテスト状態:', installData);
        } else {
            // 管理画面の場合は既存のロジックを使用
            const connectionIcon = document.getElementById('connection-test-icon');
            const sendIcon = document.getElementById('send-test-icon');
            const receiveIcon = document.getElementById('receive-test-icon');
            
            allTestsComplete = connectionIcon?.classList.contains('fa-circle-check') &&
                              sendIcon?.classList.contains('fa-circle-check') &&
                              receiveIcon?.classList.contains('fa-circle-check');
        }
        
        console.log('全テスト完了状態:', allTestsComplete);
        
        if (mainStatusDiv && mainIcon && mainTitle) {
            console.log('現在のメインアイコンクラス:', mainIcon.className);
            
            if (allTestsComplete) {
                console.log('全テスト完了 - 緑のチェックマークに変更');
                mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
                
                // メインアイコンをFontAwesomeクラス更新で変更
                console.log('メインアイコンを緑のチェックマークに変更');
                mainIcon.className = 'fas fa-check-circle text-green-400 text-xl';
                
                mainTitle.className = 'text-sm font-medium text-green-800 dark:text-green-200';
                mainTitle.textContent = '{{ __('mail.test.three_stage_test_complete') }}';
            } else {
                console.log('テスト未完了 - 黄色の警告マークに変更');
                mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
                
                // メインアイコンをFontAwesomeクラス更新で変更
                console.log('メインアイコンを黄色の警告マークに変更');
                mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
                
                mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
                mainTitle.textContent = '{{ __('mail.test.three_stage_test_incomplete') }}';
            }
            
            console.log('変更後のメインアイコンクラス:', mainIcon.className);
        } else {
            console.log('メインステータス要素が見つからないため処理をスキップ');
        }
    }

    // メール受信テスト完了の監視（localStorage経由）
    window.addEventListener('storage', function(e) {
        if (e.key === 'mail_receive_test_completed' && e.newValue === 'true') {
            console.log('メール受信テスト完了を検出');
            const testDate = localStorage.getItem('mail_receive_test_date');
            updateTestStatus('receive', true, testDate);
            
            // localStorage をクリア
            localStorage.removeItem('mail_receive_test_completed');
            localStorage.removeItem('mail_receive_test_date');
        }
    });

    // localStorage監視によるフォールバック機能
    function checkLocalStorageForMailTest() {
        try {
            const storedData = localStorage.getItem('mail_receive_test_completed');
            if (storedData) {
                const data = JSON.parse(storedData);
                const now = Date.now();
                // 5分以内のデータのみ有効とする
                if (now - data.timestamp < 300000) {
                    console.log('localStorage経由でメール受信テスト完了を検出');
                    updateTestStatus('receive', true, null);
                    
                    // メインステータスも更新
                    updateInstallMainStatus();
                    
                    showNotification('success', data.message);
                    
                    // 使用済みデータを削除
                    localStorage.removeItem('mail_receive_test_completed');
                    
                    @if($context === 'admin')
                    // 管理画面用：セッションに受信テスト完了を記録
                    fetch('{{ route('admin.settings.base') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            _method: 'POST',
                            action: 'update_receive_test_status'
                        })
                    });
                    @endif
                }
            }
        } catch (e) {
            console.log('localStorage確認エラー:', e);
        }
    }

    // 定期的にlocalStorageをチェック
    setInterval(checkLocalStorageForMailTest, 2000);
    
    // ページ読み込み時にも一度チェック
    checkLocalStorageForMailTest();

    
    function enableMailTestButton() {
        const mailTestBtn = document.getElementById('test-mail-btn');
        if (mailTestBtn) {
            mailTestBtn.disabled = false;
            mailTestBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white';
        }
    }
    
    function updateInstallMainStatus() {
        console.log('updateInstallMainStatus関数呼び出し');
        
        const mainStatusDiv = document.querySelector('.mt-6.p-4.border.rounded-lg');
        const mainIcon = document.getElementById('status-icon');
        const mainTitle = document.getElementById('status-title');
        
        if (!mainStatusDiv || !mainIcon || !mainTitle) {
            console.log('メインステータス要素が見つからない');
            return;
        }
        
        // 各テストの完了状態をチェック
        const connectionIcon = document.getElementById('connection-test-icon');
        const sendIcon = document.getElementById('send-test-icon');
        const receiveIcon = document.getElementById('receive-test-icon');
        
        const connectionComplete = connectionIcon && connectionIcon.classList.contains('text-green-600');
        const sendComplete = sendIcon && sendIcon.classList.contains('text-green-600');
        const receiveComplete = receiveIcon && receiveIcon.classList.contains('text-green-600');
        
        const allTestsComplete = connectionComplete && sendComplete && receiveComplete;
        
        if (allTestsComplete) {
            mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
            mainIcon.className = 'fas fa-check-circle text-green-400 text-xl';
            mainTitle.className = 'text-sm font-medium text-green-800 dark:text-green-200';
            mainTitle.textContent = '{{ __('mail.test.three_stage_test_complete') }}';
        } else {
            mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
            mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
            mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
            mainTitle.textContent = '{{ __('mail.test.three_stage_test_incomplete') }}';
        }
    }
    
    function showNotification(type, message) {
        const existingNotification = document.getElementById('mail-test-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.id = 'mail-test-notification';
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
            type === 'success' 
                ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200'
                : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="${type === 'success' ? 'fas fa-check-circle text-green-400' : 'fas fa-times-circle text-red-400'} text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex ${type === 'success' ? 'text-green-400 hover:text-green-500' : 'text-red-400 hover:text-red-500'}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
});
</script>
