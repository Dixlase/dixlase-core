{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                {{ __("You're logged in!") }}
            </div>
        </div>

        {{-- リファクタリング済みコンポーネントのテストセクション --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    🧪 リファクタリング済みコンポーネントのテスト
                </h2>

                {{-- Tooltip テスト --}}
                <div class="space-y-3">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">
                        1. Tooltip コンポーネント
                    </h3>
                    <div class="flex items-center gap-4">
                        <x-ui.tooltip title="クリック型ツールチップ" trigger="click">
                            <button class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md">
                                クリックしてツールチップを表示
                            </button>
                            <x-slot name="content">
                                <p>これはクリック型のツールチップです。</p>
                                <p class="mt-2 text-xs">Alpine.jsでリファクタリング済み ✨</p>
                            </x-slot>
                        </x-tooltip>

                        <x-ui.tooltip title="ホバー型ツールチップ" trigger="hover" position="top">
                            <button class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md">
                                ホバーでツールチップを表示
                            </button>
                            <x-slot name="content">
                                <p>これはホバー型のツールチップです。</p>
                                <p class="mt-2 text-xs">インラインスクリプト不要 🎉</p>
                            </x-slot>
                        </x-tooltip>
                    </div>
                </div>

                {{-- Color Picker テスト --}}
                <div class="space-y-3">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">
                        2. Color Picker コンポーネント
                    </h3>
                    <div class="max-w-md">
                        <x-form.color
                            name="test_color"
                            label="テーマカラー"
                            value="#3b82f6"
                            help="カラーピッカーとテキスト入力が自動同期されます（x-model使用）"
                        />
                    </div>
                </div>

                {{-- Email Input テスト --}}
                <div class="space-y-3">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">
                        3. Email Input コンポーネント
                    </h3>
                    <div class="max-w-md space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">パターン1: 確認欄常時表示</h4>
                            <x-email-input
                                name="test_email_1"
                                id="test_email_1"
                                value="test@example.com"
                                :showConfirmation="true"
                                :required="false"
                            />
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">パターン2: 変更時のみ確認欄表示</h4>
                            <x-email-input
                                name="test_email_2"
                                id="test_email_2"
                                value="user@example.com"
                                :showConfirmation="true"
                                :showConfirmationOnChange="true"
                                :required="false"
                            />
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                ※ メールアドレスを変更すると確認欄が表示されます
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Modal テスト --}}
                <div class="space-y-3">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">
                        4. Modal コンポーネント
                    </h3>
                    <div class="max-w-md space-y-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            各ボタンをクリックしてモーダルを表示します。
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button @click="openModal('test-modal-info')" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-info-circle mr-2"></i>情報モーダル
                            </button>
                            <button @click="openModal('test-modal-warning')" 
                                    class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors">
                                <i class="fas fa-exclamation-triangle mr-2"></i>警告モーダル
                            </button>
                            <button @click="openModal('test-modal-danger')" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                <i class="fas fa-times-circle mr-2"></i>危険モーダル
                            </button>
                        </div>
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-xs space-y-1">
                            <p class="font-semibold text-gray-700 dark:text-gray-300">機能テスト:</p>
                            <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1">
                                <li>✅ 3種類のモーダルタイプ（情報、警告、危険）</li>
                                <li>🎨 タイプ別のアイコンと色</li>
                                <li>❌ キャンセル/確認ボタン</li>
                                <li>⌨️ ESCキーで閉じる</li>
                                <li>🖱️ 背景クリックで閉じる</li>
                                <li>✨ スムーズなアニメーション</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Notification テスト --}}
                <div class="space-y-3">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">
                        5. Notification コンポーネント
                    </h3>
                    <div class="max-w-md space-y-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            各ボタンをクリックして通知を表示します。通知は5秒後に自動的に消えます。
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button @click="showSuccess('成功しました！')" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-check-circle mr-2"></i>成功通知
                            </button>
                            <button @click="showError('エラーが発生しました')" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                <i class="fas fa-times-circle mr-2"></i>エラー通知
                            </button>
                            <button @click="showWarning('警告メッセージです')" 
                                    class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors">
                                <i class="fas fa-exclamation-triangle mr-2"></i>警告通知
                            </button>
                            <button @click="showInfo('情報メッセージです')" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-info-circle mr-2"></i>情報通知
                            </button>
                        </div>
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-xs space-y-1">
                            <p class="font-semibold text-gray-700 dark:text-gray-300">機能テスト:</p>
                            <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1">
                                <li>✅ 4種類の通知タイプ（成功、エラー、警告、情報）</li>
                                <li>🎨 タイプ別の色とアイコン</li>
                                <li>⏱️ 5秒後に自動消去</li>
                                <li>❌ 閉じるボタンで手動消去</li>
                                <li>✨ スムーズなアニメーション</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Password Tools テスト --}}
                <div class="space-y-3">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">
                        5. Password Tools コンポーネント
                    </h3>
                    <div class="max-w-md space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">パターン1: 確認欄常時表示</h4>
                            <x-password-tools
                                name="test_password_1"
                                id="test_password_1"
                                :showConfirmation="true"
                                :required="false"
                                :minLength="8"
                                :recommendedLength="12"
                                :requireUppercase="true"
                                :requireLowercase="true"
                                :requireNumber="true"
                                :requireSymbol="false"
                            />
                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-xs space-y-1">
                                <p class="font-semibold text-gray-700 dark:text-gray-300">機能テスト:</p>
                                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>🔄 自動生成ボタン（緑）- ランダムパスワード生成</li>
                                    <li>📋 コピーボタン（青）- クリップボードにコピー</li>
                                    <li>👁️ 表示切り替えボタン（グレー）- パスワード表示/非表示</li>
                                    <li>📊 強度バー - リアルタイムで強度を表示</li>
                                    <li>✅ 要件チェック - 5項目の要件を自動判定</li>
                                    <li>🔒 一致判定 - 確認欄との一致を自動チェック</li>
                                </ul>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">パターン2: 入力時のみ確認欄表示</h4>
                            <x-password-tools
                                name="test_password_2"
                                id="test_password_2"
                                :showConfirmation="true"
                                :showConfirmationOnChange="true"
                                :required="false"
                                :minLength="10"
                                :recommendedLength="16"
                                :requireUppercase="true"
                                :requireLowercase="true"
                                :requireNumber="true"
                                :requireSymbol="true"
                            />
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                ※ パスワードを入力すると確認欄が表示されます（記号必須）
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>✅ CSP準拠:</strong> すべてのインラインスクリプトを削除し、Alpine.jsで実装しました。
                    </p>
                    <p class="text-sm text-blue-800 dark:text-blue-200 mt-2">
                        <strong>📁 新構造:</strong> コンポーネントJSは <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded">resources/src/components/</code> に配置されています。
                    </p>
                </div>
            </div>
        </div>

        {{-- テスト用モーダル --}}
        <x-ui.modal 
            id="test-modal-info"
            icon-type="info"
            title="情報モーダル"
            message="これは情報モーダルのテストです。Alpine.jsで実装されています。"
            confirm-label="OK"
            cancel-label="キャンセル"
        />

        <x-ui.modal 
            id="test-modal-warning"
            icon-type="warning"
            title="警告モーダル"
            message="これは警告モーダルのテストです。注意が必要な操作を確認します。"
            confirm-label="続行"
            cancel-label="キャンセル"
        />

        <x-ui.modal 
            id="test-modal-danger"
            icon-type="danger"
            title="危険な操作"
            message="この操作は取り消せません。本当に実行しますか？"
            confirm-label="削除"
            cancel-label="キャンセル"
        />

         {{__('custom.welcome')}};
         {{__('admin/nav.custom.text')}};
         {{ __('reservation-plugin::admin.nav.reservations.text')}};

    </div>

    <!-- 認証方法変更確認モーダル -->
    @if($shouldShowMethodChangeModal ?? false)
        @include('two-fa.partials.method-change-modal', [
            'modalId' => 'methodChangeModal',
            'usedMethod' => $usedMethod,
            'currentMethod' => $currentMethod,
            'autoOpen' => !session('auto_generated_recovery_codes')
        ])
    @endif

    <!-- 自動生成された回復コード表示モーダル -->
    @if(isset($auto_generated_recovery_codes))
        @include('two-fa.partials.recovery-codes-modal', [
            'modalId' => 'dashboardRecoveryCodesModal',
            'title' => __('two_fa.recovery_codes.auto_generated_title'),
            'codes' => $auto_generated_recovery_codes,
            'isAutoGenerated' => true,
            'autoOpen' => true,
            'nextModal' => isset($prompt_passkey_registration) && $prompt_passkey_registration ? 'dashboardPasskeyPromptModal' : null
        ])
    @endif
    
    {{-- パスキー登録促進モーダル --}}
    @if(isset($prompt_passkey_registration) && $prompt_passkey_registration)
        @include('two-fa.partials.passkey-prompt-modal', [
            'modalId' => 'dashboardPasskeyPromptModal',
            'hasRecoveryModal' => isset($auto_generated_recovery_codes) && $auto_generated_recovery_codes ? true : false
        ])
    @endif
@endsection
