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
    <div id="mail-test-main-status" class="mt-6 p-4 border rounded-lg 
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
    // ページ読み込み時の初期化
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded - mail-test.blade.php');
        
        // インストール時はページロード時にテスト状態をクリア
        const isInstall = {{ $isInstall ? 'true' : 'false' }};
        if (isInstall) {
            console.log('=== インストール時：テスト状態をクリア ===');
            sessionStorage.removeItem('mail_connection_tested');
            sessionStorage.removeItem('mail_send_tested');
            sessionStorage.removeItem('mail_receive_tested');
            sessionStorage.removeItem('mail_connection_test_date');
            sessionStorage.removeItem('mail_send_test_date');
            sessionStorage.removeItem('mail_receive_test_date');
            sessionStorage.removeItem('install_data');
            console.log('✅ セッションストレージをクリアしました');
        }
        
        // ページ読み込み時にセッションストレージから受信テスト完了状態をチェック
        checkReceiveTestCompletion();
        
        // BroadcastChannelでタブ間通信を受信
        try {
            const channel = new BroadcastChannel('mail_test_channel');
            channel.addEventListener('message', function(event) {
                console.log('=== BroadcastChannelメッセージ受信 ===');
                console.log('受信データ:', event.data);
                
                if (event.data && event.data.type === 'mail_receive_test_completed') {
                    console.log('✅ 受信テスト完了メッセージを受信（BroadcastChannel）');
                    
                    // 受信テストのステータスを更新
                    const testDate = new Date().toLocaleString();
                    updateTestStatus('receive', true, testDate);
                    showNotification('success', 'メール受信確認が完了しました');
                }
            });
            console.log('✅ BroadcastChannelリスナーを設定しました');
        } catch (e) {
            console.error('❌ BroadcastChannelの設定に失敗しました:', e);
        }
        
        // 接続テストボタンのイベントリスナー
        const connectionTestBtn = document.getElementById('test-connection-btn');
        console.log('接続テストボタン要素:', connectionTestBtn);
        if (connectionTestBtn) {
            console.log('接続テストボタンが見つかりました');
            connectionTestBtn.addEventListener('click', function() {
                console.log('接続テストボタンがクリックされました');
                testConnection();
            });
        } else {
            console.error('接続テストボタンが見つかりません - ID: test-connection-btn');
        }
        
        // メール送信テストボタンのイベントリスナー
        const mailTestBtn = document.getElementById('test-mail-btn');
        console.log('メール送信テストボタン要素:', mailTestBtn);
        if (mailTestBtn) {
            console.log('メール送信テストボタンが見つかりました');
            mailTestBtn.addEventListener('click', function() {
                console.log('メール送信テストボタンがクリックされました');
                testMail();
            });
        } else {
            console.error('メール送信テストボタンが見つかりません - ID: test-mail-btn');
        }
        
        // メール受信確認完了メッセージを受信
        window.addEventListener('message', function(event) {
            console.log('=== postMessage受信 ===');
            console.log('event.data:', event.data);
            console.log('event.origin:', event.origin);
            console.log('event.source:', event.source);
            console.log('データ型:', typeof event.data);
            
            // MetaMaskなどの不要なメッセージをフィルタリング
            if (event.data && typeof event.data === 'object' && event.data.target && event.data.target.includes('metamask')) {
                console.log('MetaMaskメッセージを無視');
                return;
            }
            
            console.log('メッセージのtype:', event.data?.type);
            
            if (event.data && event.data.type === 'mail_receive_test_completed') {
                console.log('✅ メール受信テスト完了メッセージを受信');
                console.log('メッセージ内容:', event.data.message);
                
                // 受信テストのステータスを更新
                console.log('updateTestStatus("receive", true)を実行...');
                updateTestStatus('receive', true, new Date().toLocaleString());
                showNotification('success', event.data.message);
            } else {
                console.log('❌ 受信テスト完了メッセージではありません');
            }
            console.log('=== postMessage処理終了 ===');
        });
        
        // 手動テスト用のボタンを追加（デバッグ用）
        console.log('現在のホスト名:', window.location.hostname);
        console.log('=== デバッグボタン作成開始 ===');
        
        // 受信テストアイコンの存在確認
        const receiveTestIcon = document.getElementById('receive-test-icon');
        console.log('receive-test-icon要素の存在確認:', receiveTestIcon);
        
        // 常にデバッグボタンを表示（開発時）
        {
            const debugBtn = document.createElement('button');
            debugBtn.type = 'button';
            debugBtn.textContent = 'デバッグ: 受信テスト完了をシミュレート';
            debugBtn.className = 'mt-2 px-3 py-1 bg-purple-500 text-white text-xs rounded';
            debugBtn.onclick = function(event) {
                event.preventDefault();
                event.stopPropagation();
                
                console.log('=== デバッグ: 受信テスト完了をシミュレート開始 ===');
                console.log('updateTestStatus関数:', typeof updateTestStatus);
                console.log('showNotification関数:', typeof showNotification);
                
                // 受信テストアイコンの存在確認
                const receiveIcon = document.getElementById('receive-test-icon');
                console.log('receive-test-icon要素:', receiveIcon);
                
                // updateTestStatus実行
                console.log('updateTestStatus("receive", true)を実行中...');
                updateTestStatus('receive', true, new Date().toLocaleString());
                
                // 実行後の状態確認
                setTimeout(() => {
                    const receiveIconAfter = document.getElementById('receive-test-icon');
                    console.log('実行後のreceive-test-icon要素:', receiveIconAfter);
                    console.log('実行後のクラス:', receiveIconAfter?.className);
                }, 100);
                
                showNotification('success', 'メール受信確認が完了しました（デバッグ）');
                console.log('=== デバッグ: 受信テスト完了をシミュレート終了 ===');
                
                return false;
            };
            
            // test-result要素に追加、見つからない場合はボタンの後に追加
            const testResultDiv = document.getElementById('test-result');
            const mailTestBtn = document.getElementById('test-mail-btn');
            
            console.log('test-result要素:', testResultDiv);
            console.log('test-mail-btn要素:', mailTestBtn);
            
            if (testResultDiv) {
                testResultDiv.appendChild(debugBtn);
                // hiddenクラスを一時的に削除してデバッグボタンを表示
                testResultDiv.classList.remove('hidden');
                console.log('✅ デバッグボタンをtest-result要素に追加しました');
                console.log('✅ test-result要素のhiddenクラスを削除しました');
                console.log('追加後のtest-result内容:', testResultDiv.innerHTML);
            } else if (mailTestBtn && mailTestBtn.parentElement) {
                mailTestBtn.parentElement.appendChild(debugBtn);
                console.log('✅ デバッグボタンをメール送信テストボタンの親要素に追加しました');
            } else {
                // 最後の手段として body に追加
                document.body.appendChild(debugBtn);
                console.log('⚠️ デバッグボタンをbodyに追加しました（フォールバック）');
            }
            
            // デバッグボタンが実際に存在するか確認
            setTimeout(() => {
                const addedBtn = document.querySelector('button[class*="bg-purple-500"]');
                console.log('追加されたデバッグボタンの確認:', addedBtn);
                console.log('デバッグボタンのテキスト:', addedBtn?.textContent);
            }, 100);
        }
    });

    // 受信テスト完了状態をチェックする関数
    function checkReceiveTestCompletion() {
        console.log('=== 受信テスト完了状態をチェック ===');
        
        try {
            const receiveTestCompleted = sessionStorage.getItem('mail_receive_test_completed');
            const receiveTestDate = sessionStorage.getItem('mail_receive_test_date');
            
            console.log('セッションストレージの受信テスト完了:', receiveTestCompleted);
            console.log('セッションストレージの受信テスト日付:', receiveTestDate);
            
            if (receiveTestCompleted === 'true') {
                console.log('✅ 受信テストが完了していることを検出');
                
                // 受信テストのステータスを更新
                updateTestStatus('receive', true, receiveTestDate);
                
                // セッションストレージから削除（一度だけ処理）
                sessionStorage.removeItem('mail_receive_test_completed');
                sessionStorage.removeItem('mail_receive_test_date');
                
                // 成功通知を表示
                showNotification('success', 'メール受信確認が完了しました');
                
                console.log('✅ 受信テスト完了処理が完了しました');
            } else {
                console.log('受信テストはまだ完了していません');
            }
        } catch (e) {
            console.error('❌ 受信テスト完了状態のチェックに失敗しました:', e);
        }
    }

    // 接続テスト関数
    function testConnection() {
        console.log('testConnection関数が呼び出されました');
        
        const connectionTestRoute = '{{ $connectionTestRoute ?? "" }}';
        console.log('接続テストルート:', connectionTestRoute);
        
        if (!connectionTestRoute) {
            console.error('{{ __('mail.js_messages.test_route_not_set') }}');
            showTestResult('error', '{{ __('mail.js_messages.test_route_not_set') }}');
            return;
        }

        // ボタンを無効化
        const btn = document.getElementById('test-connection-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = '{{ __('mail.js_messages.testing') }}';
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
                showTestResult('success', data.message || '{{ __('mail.js_messages.connection_test_success_default') }}');
            } else {
                const failedMessage = data.message || '{{ __('mail.js_messages.connection_test_failed_default') }}';
                const noteMessage = '{{ __('mail.js_messages.mail_test_failed_side_note') }}';
                showTestResult('error', failedMessage + ' ' + noteMessage);
            }
        })
        .catch(error => {
            console.error('接続テストエラー:', error);
            const mainMessage = '{{ __('mail.js_messages.connection_test_error') }}';
            const noteMessage = '{{ __('mail.js_messages.mail_test_failed_side_note') }}';
            const errorDetails = ': ' + error.message;
            showTestResult('error', mainMessage + noteMessage + errorDetails);
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
            console.error('{{ __('mail.js_messages.mail_test_route_not_set') }}');
            showTestResult('error', '{{ __('mail.js_messages.mail_test_route_not_set') }}');
            return;
        }

        // 接続テストが完了しているかチェック
        const isInstall = {{ $isInstall ? 'true' : 'false' }};
        if (isInstall) {
            const installData = JSON.parse(sessionStorage.getItem('install_data') || '{}');
            if (!installData.mail_connection_tested) {
                showTestResult('error', '{{ __('mail.js_messages.connection_test_first') }}');
                return;
            }
        }

        // ボタンを無効化
        const btn = document.getElementById('test-mail-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = '{{ __('mail.js_messages.testing') }}';
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
                showTestResult('success', data.message || '{{ __('mail.js_messages.mail_test_success_default') }}');
            } else {
                const failedMessage = data.message || '{{ __('mail.js_messages.mail_test_failed_default') }}';
                const noteMessage = '{{ __('mail.js_messages.mail_test_failed_side_note') }}';
                showTestResult('error', failedMessage + ' ' + noteMessage);
            }
        })
        .catch(error => {
            console.error('メール送信テストエラー:', error);
            const mainMessage = '{{ __('mail.js_messages.mail_test_error') }}';
            const noteMessage = '{{ __('mail.js_messages.mail_test_failed_side_note') }}';
            const errorDetails = ': ' + error.message;
            showTestResult('error', mainMessage + noteMessage + errorDetails);
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
        
        // デバッグ: testTypeと構築されるIDを確認
        console.log('testType:', testType);
        console.log('構築されるID:', testType + '-test-icon');
        
        // 直接IDで要素を取得してテスト
        console.log('connection-test-icon:', document.getElementById('connection-test-icon'));
        
        // コンテキストに関係なく要素を取得（存在する場合のみ更新）
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
            // 直接IDでアイコンを更新（確実に更新するため）
            if (testType === 'connection') {
                const connectionIcon = document.getElementById('connection-test-icon');
                if (connectionIcon) {
                    console.log('接続テストアイコンを直接更新');
                    connectionIcon.outerHTML = '<i class="mr-2 fas fa-circle-check text-green-600" id="connection-test-icon"></i>';
                }
            } else if (testType === 'send') {
                const sendIcon = document.getElementById('send-test-icon');
                if (sendIcon) {
                    console.log('送信テストアイコンを直接更新');
                    sendIcon.outerHTML = '<i class="mr-2 fas fa-circle-check text-green-600" id="send-test-icon"></i>';
                }
            } else if (testType === 'receive') {
                const receiveIcon = document.getElementById('receive-test-icon');
                if (receiveIcon) {
                    console.log('受信テストアイコンを直接更新');
                    receiveIcon.outerHTML = '<i class="mr-2 fas fa-circle-check text-green-600" id="receive-test-icon"></i>';
                }
            }
            
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
                // 個別キーでセッションストレージに保存
                sessionStorage.setItem(`mail_${testType}_tested`, 'true');
                if (testDate) {
                    sessionStorage.setItem(`mail_${testType}_test_date`, testDate);
                }
                
                console.log('=== セッションストレージ個別保存 ===');
                console.log(`mail_${testType}_tested:`, sessionStorage.getItem(`mail_${testType}_tested`));
                console.log(`mail_${testType}_test_date:`, sessionStorage.getItem(`mail_${testType}_test_date`));
                
                // 従来のinstall_dataも更新（互換性のため）
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
    // グローバル関数として公開
    window.updateTestStatus = updateTestStatus;
    window.showNotification = showNotification;

    // メインステータス更新関数
    function updateInstallMainStatus() {
        console.log('updateInstallMainStatus関数呼び出し');
        // メインステータス要素を取得
        const mainStatusDiv = document.getElementById('mail-test-status');
        const mainIcon = document.getElementById('status-icon');
        const mainTitle = document.getElementById('status-title');
        
        console.log('=== メインステータス要素の詳細確認 ===');
        console.log('mail-test-status要素:', mainStatusDiv);
        console.log('status-icon要素:', mainIcon);
        console.log('status-title要素:', mainTitle);
        
        console.log('メインステータス要素:', { mainStatusDiv, mainIcon, mainTitle });
        
        // テスト完了状態をチェック
        const isInstall = {{ $isInstall ? 'true' : 'false' }};
        let allTestsComplete = false;
        
        if (isInstall) {
            // インストール時はセッションストレージから現在のテスト状態を取得
            console.log('=== セッションストレージの詳細確認 ===');
            console.log('mail_connection_tested raw:', sessionStorage.getItem('mail_connection_tested'));
            console.log('mail_send_tested raw:', sessionStorage.getItem('mail_send_tested'));
            console.log('mail_receive_tested raw:', sessionStorage.getItem('mail_receive_tested'));
            
            const testData = {
                mail_connection_tested: sessionStorage.getItem('mail_connection_tested') === 'true',
                mail_send_tested: sessionStorage.getItem('mail_send_tested') === 'true', 
                mail_receive_tested: sessionStorage.getItem('mail_receive_tested') === 'true'
            };
            allTestsComplete = testData.mail_connection_tested && testData.mail_send_tested && testData.mail_receive_tested;
            console.log('インストール時のテスト状態（セッションストレージ）:', testData);
            
            // セッションストレージの全内容を確認
            console.log('=== セッションストレージ全内容 ===');
            for (let i = 0; i < sessionStorage.length; i++) {
                const key = sessionStorage.key(i);
                console.log(`${key}: ${sessionStorage.getItem(key)}`);
            }
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
        
        // メインステータス要素が存在しない場合はスキップ
        if (!mainStatusDiv || !mainIcon || !mainTitle) {
            console.log('❌ メインステータス要素が見つからないため処理をスキップ');
            console.log('mainStatusDiv存在:', !!mainStatusDiv);
            console.log('mainIcon存在:', !!mainIcon);
            console.log('mainTitle存在:', !!mainTitle);
            
            // 要素が見つからない場合、DOM全体を検索
            console.log('=== DOM全体でのID検索 ===');
            console.log('mail-test-status:', document.querySelector('#mail-test-status'));
            console.log('status-icon:', document.querySelector('#status-icon'));
            console.log('status-title:', document.querySelector('#status-title'));
            return;
        }    
        
        if (allTestsComplete) {
            console.log('全テスト完了 - 緑のチェックマークに変更');
            mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
            
            // メインアイコンを緑のチェックマークに変更
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
    }

    // メール受信テスト完了の監視（localStorage経由）
    window.addEventListener('storage', function(e) {
        if (e.key === 'mail_receive_test_completed' && e.newValue === 'true') {
            console.log('{{ __('mail.js_messages.mail_receive_test_completed') }}');
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
                    console.log('{{ __('mail.js_messages.mail_receive_test_completed') }}');
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
</script>
