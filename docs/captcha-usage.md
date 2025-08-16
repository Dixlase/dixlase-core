# reCAPTCHA Implementation Guide

## Overview

Dixlaseにマルチプロバイダー対応のreCAPTCHA機能が実装されました。現在はGoogle reCAPTCHAをサポートしており、将来的にhCaptcha、Cloudflare Turnstileなどの追加が可能な設計になっています。

## 設定方法

### 1. 管理画面での設定

1. 管理画面 > セキュリティ設定 にアクセス
2. 「reCAPTCHAを有効にする」をチェック
3. Google reCAPTCHAの設定を入力：
   - サイトキー
   - シークレットキー
   - バージョン（v3推奨）
   - 最小スコア（v3の場合、通常0.5）
4. フォーム別設定で各フォームでのreCAPTCHA使用を設定

### 2. Google reCAPTCHAキーの取得

1. [Google reCAPTCHA](https://www.google.com/recaptcha/)にアクセス
2. 新しいサイトを登録
3. サイトキーとシークレットキーを取得
4. 管理画面に入力

## 使用方法

### フォームでのreCAPTCHA表示

```blade
<!-- フォーム内でreCAPTCHAを表示 -->
<form method="POST" action="/contact">
    @csrf
    
    <!-- 他のフォームフィールド -->
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <textarea name="message" required></textarea>
    
    <!-- reCAPTCHAウィジェット -->
    <x-captcha action="contact_form" />
    
    <button type="submit">送信</button>
</form>
```

### コントローラーでの検証

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\VerifiesCaptcha;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use VerifiesCaptcha;

    public function store(Request $request)
    {
        // reCAPTCHA検証
        $this->verifyCaptcha($request, 'contact');
        
        // 通常のバリデーション
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);
        
        // フォーム処理
        // ...
        
        return redirect()->back()->with('success', 'お問い合わせを受け付けました。');
    }
}
```

### フォームリクエストでの検証

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\VerifiesCaptcha;

class ContactFormRequest extends FormRequest
{
    use VerifiesCaptcha;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ], $this->getCaptchaRules());
    }

    protected function prepareForValidation()
    {
        $this->verifyCaptcha($this, 'contact');
    }
}
```

## 設定オプション

### reCAPTCHA v3 (推奨)
- 非対話型
- スコアベース（0.0-1.0）
- UXに優しい

### reCAPTCHA v2
- チェックボックス型
- より確実だが、UXに影響

## 将来の拡張

### 新しいプロバイダーの追加

1. `app/Captcha/` に新しいドライバークラスを作成
2. `CaptchaDriver` インターフェースを実装
3. `config/captcha.php` に設定を追加
4. 管理画面のUIを更新

例：hCaptcha ドライバー

```php
<?php

namespace App\Captcha;

use Illuminate\Http\Request;

class HCaptchaDriver implements CaptchaDriver
{
    // CaptchaDriverインターフェースの実装
}
```

## トラブルシューティング

### よくある問題

1. **reCAPTCHAが表示されない**
   - サイトキーが正しく設定されているか確認
   - reCAPTCHAが有効になっているか確認

2. **検証が失敗する**
   - シークレットキーが正しく設定されているか確認
   - ドメインがreCAPTCHAに登録されているか確認

3. **スコアが低すぎる（v3の場合）**
   - 最小スコアを調整（0.3-0.7の範囲で調整）
   - ユーザーの行動パターンを確認

### ログの確認

reCAPTCHA関連のエラーは `storage/logs/laravel.log` に記録されます。

## セキュリティ考慮事項

1. シークレットキーは適切に保護する
2. 複数の防御層を組み合わせる（レート制限、ハニーポットなど）
3. 定期的にスコア閾値を見直す
4. ログを監視してパターンを把握する
