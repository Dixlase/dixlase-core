{{--
二段階認証コンポーネントの使用例

このファイルは、ユーザー管理プラグインなどで二段階認証コンポーネントを
使用する際の参考例として作成されています。

## 基本的な使用方法

### 1. メール認証のみ
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

### 2. デバイス認証のみ
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

### 3. 生体認証のみ
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

### 4. 複数認証方法（代替方法付き）
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

## プロパティ一覧

### auth-layout コンポーネント
- title: ページタイトル（デフォルト: "二段階認証"）
- subtitle: サブタイトル（オプション）
- context: コンテキスト（"admin", "user", "plugin"）

### email-challenge コンポーネント
- action: フォーム送信先URL（必須）
- resend-action: 再送信URL（オプション）
- title: チャレンジタイトル（デフォルト: "認証コード入力"）
- prompt: プロンプトメッセージ
- submit-text: 送信ボタンテキスト（デフォルト: "認証"）
- resend-text: 再送信ボタンテキスト（デフォルト: "再送信"）
- expire-minutes: 有効期限（分）（デフォルト: 10）
- code-length: コード桁数（デフォルト: 6）
- auto-submit: 自動送信（デフォルト: true）
- show-expire-time: 有効期限表示（デフォルト: true）
- show-resend: 再送信ボタン表示（デフォルト: true）
- context: コンテキスト（デフォルト: "admin"）

### device-challenge コンポーネント
- challenge-action: チャレンジ開始URL（必須）
- verify-action: 認証確認URL（必須）
- title: チャレンジタイトル（デフォルト: "デバイス認証"）
- prompt: プロンプトメッセージ
- context: コンテキスト（デフォルト: "admin"）
- poll-interval: ポーリング間隔（ミリ秒）（デフォルト: 2000）
- max-retries: 最大リトライ回数（デフォルト: 30）

### biometric-challenge コンポーネント
- challenge-action: チャレンジ開始URL（必須）
- verify-action: 認証確認URL（必須）
- title: チャレンジタイトル（デフォルト: "生体認証"）
- prompt: プロンプトメッセージ
- context: コンテキスト（デフォルト: "admin"）

### alternative-methods コンポーネント
- methods: 代替認証方法の配列（必須）
  - value: 認証方法の値
  - label: 表示ラベル
  - url: リンク先URL
- current-method: 現在の認証方法（オプション）
- context: コンテキスト（デフォルト: "admin"）

## カスタマイズ例

### 独自レイアウトでの使用
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

### 動的な代替方法
<x-two-factor.alternative-methods
    :methods="$availableMethods"
    :current-method="$currentMethod"
    context="user"
/>

--}}
