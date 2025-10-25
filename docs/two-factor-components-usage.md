# 二段階認証コンポーネントの使用例

このドキュメントは、ユーザー管理プラグインなどで二段階認証コンポーネントを使用する際の参考例です。

## 基本的な使用方法

### 1. メール認証のみ

```blade
<x-two-factor.auth-layout 
    title="二段階認証"
    subtitle="メールに送信された認証コードを入力してください"
    context="user"
>
    <x-two-factor.email-challenge
        :action="route('user.two-factor.verify')"
        :resend-action="route('user.two-factor.resend')"
        title="認証コード入力"
        prompt="送信された6桁のコードを入力してください"
        context="user"
    />
</x-two-factor.auth-layout>
```

### 2. デバイス認証のみ

```blade
<x-two-factor.auth-layout 
    title="デバイス認証"
    subtitle="登録済みデバイスで認証を承認してください"
    context="user"
>
    <x-two-factor.device-challenge
        :challenge-action="route('user.two-factor.device.challenge')"
        :verify-action="route('user.two-factor.device.verify')"
        title="デバイス認証"
        prompt="デバイスでの認証を確認してください"
        context="user"
    />
</x-two-factor.auth-layout>
```

### 3. 生体認証のみ

```blade
<x-two-factor.auth-layout 
    title="生体認証"
    subtitle="生体認証を使用してログインしてください"
    context="user"
>
    <x-two-factor.biometric-challenge
        :challenge-action="route('user.two-factor.biometric.challenge')"
        :verify-action="route('user.two-factor.biometric.verify')"
        title="生体認証"
        prompt="Touch ID、Face ID等を使用してください"
        context="user"
    />
</x-two-factor.auth-layout>
```

### 4. 複数認証方法（代替方法付き）

```blade
<x-two-factor.auth-layout 
    title="二段階認証"
    subtitle="認証方法を選択してください"
    context="user"
>
    <x-two-factor.email-challenge
        :action="route('user.two-factor.verify')"
        :resend-action="route('user.two-factor.resend')"
        title="メール認証"
        prompt="メールに送信されたコードを入力してください"
        context="user"
    />

    <x-slot name="alternatives">
        <x-two-factor.alternative-methods
            :methods="[
                [
                    'value' => 'device',
                    'label' => 'デバイス認証を使用',
                    'url' => route('user.two-factor.device.challenge')
                ],
                [
                    'value' => 'biometric',
                    'label' => '生体認証を使用',
                    'url' => route('user.two-factor.biometric.challenge')
                ]
            ]"
            current-method="email"
            context="user"
        />
    </x-slot>
</x-two-factor.auth-layout>
```

## プロパティ一覧

### auth-layout コンポーネント

| プロパティ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| `title` | string | "二段階認証" | ページタイトル |
| `subtitle` | string | null | サブタイトル（オプション） |
| `context` | string | "admin" | コンテキスト（"admin", "user", "plugin"） |

### email-challenge コンポーネント

| プロパティ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| `action` | string | **必須** | フォーム送信先URL |
| `resend-action` | string | null | 再送信URL（オプション） |
| `title` | string | "認証コード入力" | チャレンジタイトル |
| `prompt` | string | null | プロンプトメッセージ |
| `submit-text` | string | "認証" | 送信ボタンテキスト |
| `resend-text` | string | "再送信" | 再送信ボタンテキスト |
| `expire-minutes` | int | 10 | 有効期限（分） |
| `code-length` | int | 6 | コード桁数 |
| `auto-submit` | bool | true | 自動送信 |
| `show-expire-time` | bool | true | 有効期限表示 |
| `show-resend` | bool | true | 再送信ボタン表示 |
| `context` | string | "admin" | コンテキスト |

### device-challenge コンポーネント

| プロパティ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| `challenge-action` | string | **必須** | チャレンジ開始URL |
| `verify-action` | string | **必須** | 認証確認URL |
| `title` | string | "デバイス認証" | チャレンジタイトル |
| `prompt` | string | null | プロンプトメッセージ |
| `context` | string | "admin" | コンテキスト |
| `poll-interval` | int | 2000 | ポーリング間隔（ミリ秒） |
| `max-retries` | int | 30 | 最大リトライ回数 |

### biometric-challenge コンポーネント

| プロパティ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| `challenge-action` | string | **必須** | チャレンジ開始URL |
| `verify-action` | string | **必須** | 認証確認URL |
| `title` | string | "生体認証" | チャレンジタイトル |
| `prompt` | string | null | プロンプトメッセージ |
| `context` | string | "admin" | コンテキスト |

### alternative-methods コンポーネント

| プロパティ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| `methods` | array | **必須** | 代替認証方法の配列 |
| `current-method` | string | null | 現在の認証方法（オプション） |
| `context` | string | "admin" | コンテキスト |

#### methods配列の構造

```php
[
    [
        'value' => 'email',      // 認証方法の値
        'label' => 'メール認証',  // 表示ラベル
        'url' => route('...')    // リンク先URL
    ],
    // ...
]
```

## カスタマイズ例

### 独自レイアウトでの使用

```blade
@extends('your-layout')

@section('content')
<div class="your-custom-container">
    <x-two-factor.email-challenge
        :action="route('custom.verify')"
        title="カスタム認証"
        prompt="カスタムメッセージ"
        :code-length="4"
        :expire-minutes="5"
        :auto-submit="false"
        context="plugin"
    />
</div>
@endsection
```

### 動的な代替方法

```blade
<x-two-factor.alternative-methods
    :methods="$availableMethods"
    :current-method="$currentMethod"
    context="user"
/>
```

## 関連ドキュメント

- [二段階認証システム概要](./two-factor-authentication-usage.md)
- [旧コンポーネント使用例](./two-factor-challenge-component-usage.md)
