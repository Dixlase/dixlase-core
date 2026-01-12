# 二段階認証（2FA）UIコンポーネント

このドキュメントは、Dixlaseの二段階認証システムで使用するUIコンポーネントの使用方法を説明します。

> **📚 関連ドキュメント**
> - [2FAアーキテクチャ](./two-factor-authentication-architecture.md) - 技術仕様と詳細
> - [2FA実践ガイド](./two-factor-authentication-guide.md) - バックエンド実装

---

## 概要

Dixlaseは、二段階認証のUIを簡単に実装できる再利用可能なBladeコンポーネントを提供しています。これらのコンポーネントは、管理画面、ユーザープラグイン、カスタムプラグインで共通して使用できます。

### 提供されるコンポーネント

1. **`<x-two-factor-challenge>`** - 6桁コード入力フォーム
2. **認証レイアウト** - `layouts.auth`

---

## 基本的な使用方法

### 最小限の実装

```blade
@extends('layouts.auth')

@section('title', '二段階認証')
@section('header', '二段階認証')
@section('description', 'メールに送信された認証コードを入力してください')

@section('content')
    <x-two-factor-challenge 
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endsection
```

---

## コンポーネント詳細

### 1. `<x-two-factor-challenge>` コンポーネント

6桁の認証コード入力フォームを提供します。

**場所**: `resources/views/components/two-factor-challenge.blade.php`

#### プロパティ

| プロパティ | 型 | デフォルト | 必須 | 説明 |
|-----------|-----|-----------|------|------|
| `action` | string | - | ✅ | フォーム送信先URL |
| `resendAction` | string | null | ❌ | 再送信URL（オプション） |
| `title` | string | `__('auth.two_factor.title')` | ❌ | タイトル |
| `prompt` | string | `__('auth.two_factor.prompt')` | ❌ | プロンプトメッセージ |
| `submitText` | string | `__('auth.two_factor.submit')` | ❌ | 送信ボタンテキスト |
| `resendText` | string | `__('auth.two_factor.resend')` | ❌ | 再送信ボタンテキスト |
| `expireMinutes` | int | 10 | ❌ | 有効期限（分） |
| `codeLength` | int | 6 | ❌ | コード桁数 |
| `autoSubmit` | bool | true | ❌ | 自動送信 |
| `showExpireTime` | bool | true | ❌ | 有効期限表示 |
| `showResend` | bool | true | ❌ | 再送信ボタン表示 |

#### 基本的な使用例

```blade
<x-two-factor-challenge 
    :action="route('admin.two-fa.email.verify')"
    :resend-action="route('admin.two-fa.email.resend')"
/>
```

#### カスタマイズ例

```blade
<x-two-factor-challenge 
    :action="route('custom.verify')"
    :resend-action="route('custom.resend')"
    title="カスタム認証"
    prompt="カスタムメッセージをここに表示"
    submit-text="確認"
    resend-text="コードを再送信"
    :expire-minutes="5"
    :code-length="4"
    :auto-submit="false"
    :show-expire-time="false"
    :show-resend="false"
/>
```

#### 機能

##### 1. 入力処理

- **数字のみ入力**: 0-9のみ受け付け
- **自動フォーカス**: 入力後、自動的に次のフィールドに移動
- **Backspace対応**: 空のフィールドでBackspaceを押すと前のフィールドに戻る
- **ペースト対応**: 6桁のコードをペーストすると自動的に各フィールドに分配

##### 2. 自動送信

`autoSubmit`が`true`（デフォルト）の場合、すべてのフィールドが入力されると自動的にフォームを送信します。

##### 3. アクセシビリティ

- `inputmode="numeric"`: モバイルで数字キーボードを表示
- `autocomplete="one-time-code"`: ブラウザの自動入力に対応
- 適切なフォーカス管理

##### 4. フォームデータ

コンポーネントは以下のデータを送信します：

```php
[
    '_token' => 'csrf_token', // 自動的に含まれる
    'code' => '123456'        // 入力されたコード
]
```

---

### 2. `layouts.auth` レイアウト

認証画面用の統一されたレイアウトを提供します。

**場所**: `resources/views/layouts/auth.blade.php`

#### セクション

| セクション | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| `@section('title')` | string | ✅ | ページタイトル（ブラウザタブ） |
| `@section('icon')` | string | ❌ | Font Awesomeアイコンクラス |
| `@section('header')` | string | ✅ | ページヘッダー |
| `@section('description')` | string | ❌ | 説明文 |
| `@section('content')` | blade | ✅ | メインコンテンツ |
| `@section('back_link')` | blade | ❌ | 戻るリンク |

#### 使用例

```blade
@extends('layouts.auth')

@section('title', '二段階認証')
@section('icon', 'fas fa-shield-alt')
@section('header', '二段階認証')
@section('description', 'セキュリティのため、追加の認証が必要です')

@section('content')
    <x-two-factor-challenge 
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← ログイン画面に戻る
    </a>
@endsection
```

---

## 実装パターン

### パターン1: メール認証のみ

```blade
@extends('layouts.auth')

@section('title', 'メール認証')
@section('icon', 'fas fa-envelope')
@section('header', 'メール認証')
@section('description', 'メールに送信された6桁のコードを入力してください')

@section('content')
    <x-two-factor-challenge 
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← ログイン画面に戻る
    </a>
@endsection
```

---

### パターン2: 代替認証方法の表示

```blade
@extends('layouts.auth')

@section('title', '二段階認証')
@section('icon', 'fas fa-shield-alt')
@section('header', '二段階認証')
@section('description', '認証方法を選択してください')

@section('content')
    {{-- メール認証フォーム --}}
    <x-two-factor-challenge 
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />

    {{-- 代替認証方法 --}}
    @if(!empty($availableMethods))
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 text-center">
                または
            </p>
            
            <div class="space-y-2">
                @foreach($availableMethods as $method)
                    <a href="{{ $method['url'] }}" 
                       class="block w-full px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        {{ $method['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 回復コードリンク --}}
    <div class="mt-4 text-center">
        <a href="{{ route('admin.two-fa.recovery.show') }}" 
           class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
            回復コードを使用
        </a>
    </div>
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← ログイン画面に戻る
    </a>
@endsection
```

**コントローラー側:**

```php
public function showEmailChallenge()
{
    $user = $this->getUserFromSession();
    
    // 利用可能な認証方法を取得
    $twoFaService = $this->getTwoFaService();
    $methods = $twoFaService->getAvailableMethods($user);
    
    // 現在の認証方法（メール）を除外
    $availableMethods = array_filter($methods, function($method) {
        return $method['value'] !== \App\Enums\TwoFaMethod::EMAIL->value;
    });
    
    // ルート情報を追加
    $availableMethods = array_map(function($method) {
        $method['url'] = match($method['value']) {
            \App\Enums\TwoFaMethod::PASSKEY->value => route('admin.two-fa.passkey.show'),
            default => '#',
        };
        return $method;
    }, $availableMethods);
    
    return view('admin.two-fa.email-challenge', [
        'availableMethods' => $availableMethods
    ]);
}
```

---

### パターン3: Passkey認証画面

```blade
@extends('layouts.auth')

@section('title', 'Passkey認証')
@section('icon', 'fas fa-fingerprint')
@section('header', 'Passkey認証')
@section('description', 'Touch ID、Face ID等を使用して認証してください')

@section('content')
    <div class="space-y-4">
        {{-- 認証ボタン --}}
        <button type="button" 
                id="passkey-auth-button"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors">
            <i class="fas fa-fingerprint mr-2"></i>
            Passkeyで認証
        </button>

        {{-- ステータス表示 --}}
        <div id="passkey-status" class="text-sm text-center text-gray-600 dark:text-gray-400 hidden">
            認証中...
        </div>

        {{-- エラー表示 --}}
        <div id="passkey-error" class="text-sm text-center text-red-600 dark:text-red-400 hidden"></div>
    </div>

    {{-- 代替認証方法 --}}
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 text-center">
            または
        </p>
        
        <a href="{{ route('admin.two-fa.email.show') }}" 
           class="block w-full px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            メール認証を使用
        </a>
    </div>
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← ログイン画面に戻る
    </a>
@endsection

@push('scripts')
<script>
document.getElementById('passkey-auth-button').addEventListener('click', async function() {
    const button = this;
    const status = document.getElementById('passkey-status');
    const error = document.getElementById('passkey-error');
    
    button.disabled = true;
    status.classList.remove('hidden');
    error.classList.add('hidden');
    
    try {
        // チャレンジ取得
        const challengeResponse = await fetch('{{ route("admin.two-fa.passkey.challenge") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        const challengeData = await challengeResponse.json();
        
        if (!challengeData.success) {
            throw new Error(challengeData.message || '認証に失敗しました');
        }
        
        // WebAuthn認証
        const credential = await navigator.credentials.get({
            publicKey: challengeData.options
        });
        
        // 認証検証
        const verifyResponse = await fetch('{{ route("admin.two-fa.passkey.verify") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                credential: {
                    id: credential.id,
                    rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
                    response: {
                        authenticatorData: btoa(String.fromCharCode(...new Uint8Array(credential.response.authenticatorData))),
                        clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))),
                        signature: btoa(String.fromCharCode(...new Uint8Array(credential.response.signature)))
                    },
                    type: credential.type
                }
            })
        });
        
        const verifyData = await verifyResponse.json();
        
        if (verifyData.success) {
            window.location.href = verifyData.redirect;
        } else {
            throw new Error(verifyData.message || '認証に失敗しました');
        }
    } catch (err) {
        error.textContent = err.message;
        error.classList.remove('hidden');
        button.disabled = false;
        status.classList.add('hidden');
    }
});
</script>
@endpush
```

---

### パターン4: 回復コード入力画面

```blade
@extends('layouts.auth')

@section('title', '回復コード入力')
@section('icon', 'fas fa-key')
@section('header', '回復コード入力')
@section('description', '20桁の回復コードを入力してください')

@section('content')
    <form method="POST" action="{{ route('admin.two-fa.recovery.verify') }}">
        @csrf
        
        <div class="space-y-4">
            {{-- 回復コード入力 --}}
            <div>
                <label for="recovery_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    回復コード
                </label>
                <input type="text" 
                       id="recovery_code" 
                       name="recovery_code" 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white @error('recovery_code') border-red-500 @enderror"
                       placeholder="12345-67890-12345-67890"
                       required
                       autofocus>
                
                @error('recovery_code')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    ハイフンは自動的に除去されます
                </p>
            </div>

            {{-- 送信ボタン --}}
            <button type="submit" 
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors">
                認証
            </button>
        </div>
    </form>

    {{-- 代替認証方法 --}}
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 text-center">
            または
        </p>
        
        <a href="{{ route('admin.two-fa.email.show') }}" 
           class="block w-full px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            メール認証に戻る
        </a>
    </div>
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← ログイン画面に戻る
    </a>
@endsection
```

---

## プラグイン開発での使用

### ユーザープラグインでの実装例

```blade
{{-- plugins/DixlaseUsers/resources/views/two-fa/email-challenge.blade.php --}}

@extends('users-plugin::layouts.auth')

@section('title', '二段階認証')
@section('header', '二段階認証')
@section('description', 'メールに送信された認証コードを入力してください')

@section('content')
    <x-two-factor-challenge 
        :action="route('users-plugin.two-fa.email.verify')"
        :resend-action="route('users-plugin.two-fa.email.resend')"
        :title="__('users-plugin::auth.two_factor.title')"
        :prompt="__('users-plugin::auth.two_factor.prompt')"
        :submit-text="__('users-plugin::auth.two_factor.submit')"
        :resend-text="__('users-plugin::auth.two_factor.resend')"
    />
@endsection

@section('back_link')
    <a href="{{ route('users-plugin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← ログイン画面に戻る
    </a>
@endsection
```

---

## カスタマイズ

### スタイルのカスタマイズ

コンポーネントはTailwind CSSを使用しており、ダークモードに対応しています。

**カスタムCSSを追加する場合:**

```blade
@push('styles')
<style>
/* カスタムスタイル */
.two-factor-input {
    /* カスタムスタイルを追加 */
}
</style>
@endpush
```

### JavaScriptのカスタマイズ

コンポーネントのJavaScript動作をカスタマイズする場合：

```blade
@push('scripts')
<script>
// コンポーネントのイベントをリッスン
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="{{ route('admin.two-fa.email.verify') }}"]');
    
    form.addEventListener('submit', function(e) {
        // カスタム処理
        console.log('Form submitting...');
    });
});
</script>
@endpush
```

---

## 翻訳キー

### デフォルトの翻訳キー

コンポーネントは以下の翻訳キーを使用します：

```php
// lang/ja/auth.php
'two_factor' => [
    'title' => '二段階認証',
    'prompt' => 'メールに送信された6桁のコードを入力してください',
    'submit' => '認証',
    'resend' => '再送信',
    'expire_message' => 'コードは:minutes分間有効です',
],
```

### カスタム翻訳キーの使用

```blade
<x-two-factor-challenge 
    :action="route('custom.verify')"
    :title="__('custom.two_factor.title')"
    :prompt="__('custom.two_factor.prompt')"
    :submit-text="__('custom.two_factor.submit')"
    :resend-text="__('custom.two_factor.resend')"
/>
```

---

## トラブルシューティング

### コンポーネントが表示されない

**原因**: コンポーネントファイルが見つからない

**解決策**:
```bash
# コンポーネントファイルの存在を確認
ls resources/views/components/two-factor-challenge.blade.php
```

### 自動送信が動作しない

**原因**: JavaScriptエラー

**解決策**:
1. ブラウザのコンソールを確認
2. `autoSubmit`プロパティが`true`になっているか確認
3. JavaScriptが正しく読み込まれているか確認

### スタイルが適用されない

**原因**: Tailwind CSSが読み込まれていない

**解決策**:
```blade
{{-- レイアウトでTailwind CSSを読み込む --}}
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
```

---

## ベストプラクティス

### 1. 一貫性のあるレイアウト使用

```blade
{{-- 推奨: layouts.authを使用 --}}
@extends('layouts.auth')

{{-- 非推奨: カスタムレイアウトを毎回作成 --}}
@extends('custom-layout')
```

### 2. エラーメッセージの表示

```blade
@if($errors->any())
    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
        <p class="text-sm text-red-600 dark:text-red-400">
            {{ $errors->first() }}
        </p>
    </div>
@endif

<x-two-factor-challenge 
    :action="route('admin.two-fa.email.verify')"
    :resend-action="route('admin.two-fa.email.resend')"
/>
```

### 3. ロックアウト状態の表示

```blade
@if(session('lockout'))
    <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
        <p class="text-sm text-yellow-600 dark:text-yellow-400">
            ロックアウト中です。残り{{ session('lockout_minutes') }}分お待ちください。
        </p>
    </div>
@else
    <x-two-factor-challenge 
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endif
```

### 4. 残り試行回数の表示

```blade
@if(isset($remaining_attempts) && $remaining_attempts <= 3)
    <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
        <p class="text-sm text-yellow-600 dark:text-yellow-400">
            残り{{ $remaining_attempts }}回の試行が可能です。
        </p>
    </div>
@endif

<x-two-factor-challenge 
    :action="route('admin.two-fa.email.verify')"
    :resend-action="route('admin.two-fa.email.resend')"
/>
```

---

## まとめ

Dixlaseの2FA UIコンポーネントは、以下の特徴を持っています：

✅ **簡単な実装**: 1行のコードで完全な認証フォームを追加
✅ **再利用可能**: 管理画面、プラグインで共通使用
✅ **カスタマイズ可能**: プロパティで柔軟に調整
✅ **アクセシビリティ**: モバイル対応、キーボード操作対応
✅ **ダークモード対応**: 自動的にダークモードに対応
✅ **多言語対応**: 翻訳キーで簡単に多言語化

これらのコンポーネントを使用することで、統一感のある美しい2FA認証画面を簡単に実装できます。

---

## 関連ドキュメント

- [2FAアーキテクチャ](./two-factor-authentication-architecture.md) - 技術仕様と詳細
- [2FA実践ガイド](./two-factor-authentication-guide.md) - バックエンド実装
