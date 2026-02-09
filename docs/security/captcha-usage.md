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

## プラグイン・テーマでのCAPTCHA実装

### アクション名の命名規則

プラグインやテーマでCAPTCHAを実装する場合、アクション名にプレフィックスを付ける必要があります。

**命名規則:**
- **コア機能**: `admin_login`, `user_register` など
- **プラグイン**: `{plugin-slug}.{action}` 形式
- **テーマ**: `{theme-slug}.{action}` 形式

**例:**
- DixlaseUsersプラグイン: `dixlase-users.user_register`
- DixlaseUsersプラグイン: `dixlase-users.user_profile_update`
- DixlaseBlogプラグイン: `dixlase-blog.comment_submit`

### コントローラーでの実装

#### 1. getCaptchaAction()メソッドの実装

プラグインのコントローラーで`getCaptchaAction()`メソッドを実装し、プレフィックス付きアクション名を返します。

```php
<?php

namespace Plugins\DixlaseUsers\App\Http\Controllers\Front\Register;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DixlaseUsersRegisterController extends Controller
{
    /**
     * CAPTCHAアクション名を返す
     */
    protected function getCaptchaAction(): string
    {
        return 'dixlase-users.user_register';
    }

    /**
     * 登録フォームを表示
     */
    public function create()
    {
        // CAPTCHA設定を取得
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        return view('dixlase-users::front.register.create', [
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
        ]);
    }
}
```

#### 2. ビューでのCAPTCHA表示

ビューでは、コントローラーから渡された`$captchaEnabled`と`$captchaWidget`を使用してCAPTCHAを表示します。

```blade
<form method="POST" action="{{ route('dixlase-users.register.store') }}">
    @csrf
    
    <!-- フォームフィールド -->
    <div class="form-group">
        <label for="name">名前</label>
        <input type="text" name="name" id="name" required>
    </div>
    
    <div class="form-group">
        <label for="email">メールアドレス</label>
        <input type="email" name="email" id="email" required>
    </div>
    
    <!-- CAPTCHAウィジェット -->
    @if($captchaEnabled ?? false)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            {!! $captchaWidget !!}
        </div>
    @endif
    
    <button type="submit" class="btn btn-primary">登録</button>
</form>
```

#### 3. CAPTCHA検証

フォーム送信時に`VerifiesCaptcha`トレイトを使用してCAPTCHA検証を行います。

```php
<?php

namespace Plugins\DixlaseUsers\App\Http\Controllers\Front\Register;

use App\Http\Controllers\Controller;
use App\Traits\VerifiesCaptcha;
use Illuminate\Http\Request;

class DixlaseUsersRegisterController extends Controller
{
    use VerifiesCaptcha;

    protected function getCaptchaAction(): string
    {
        return 'dixlase-users.user_register';
    }

    public function store(Request $request)
    {
        // CAPTCHA検証
        $this->verifyCaptcha($request, $this->getCaptchaAction());
        
        // 通常のバリデーション
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        // 登録処理
        // ...
        
        return redirect()->route('dixlase-users.register.complete')
            ->with('success', '登録が完了しました。');
    }
}
```

### プロフィール更新など複数フォームでの共通化

複数のフォームで同じCAPTCHAアクション名を使用する場合の例です。

```php
<?php

namespace Plugins\DixlaseUsers\App\Http\Controllers\Mypage\Profile;

use App\Http\Controllers\Controller;
use App\Traits\VerifiesCaptcha;
use Illuminate\Http\Request;

class DixlaseUsersMypageProfileController extends Controller
{
    use VerifiesCaptcha;

    /**
     * プロフィール更新用の共通CAPTCHAアクション名
     */
    protected function getCaptchaAction(): string
    {
        return 'dixlase-users.user_profile_update';
    }

    /**
     * 基本情報フォーム表示
     */
    public function basicInfo(Request $request)
    {
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);
        
        return view('dixlase-users::mypage.profile.basic-info', [
            'user' => $request->user(),
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
        ]);
    }

    /**
     * 外観設定フォーム表示
     */
    public function appearance(Request $request)
    {
        // 同じCAPTCHAアクション名を使用
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);
        
        return view('dixlase-users::mypage.profile.appearance', [
            'user' => $request->user(),
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
        ]);
    }
}
```

### CSP対応

Alpine.jsを使用する保存ボタンなどでは、`x-data`スコープが必要です。

```blade
<div x-data="{}">
    <form method="POST" action="{{ route('dixlase-users.profile.update') }}">
        @csrf
        
        <!-- フォームフィールド -->
        <input type="text" name="name" value="{{ $user->name }}">
        
        <!-- CAPTCHAウィジェット -->
        @if($captchaEnabled ?? false)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                {!! $captchaWidget !!}
            </div>
        @endif
        
        <!-- 保存ボタン（Alpine.js使用） -->
        <x-form-button
            type="button"
            variant="primary"
            :label="__('common.save')"
            icon="fas fa-save"
            :x-click="'openModal(\'confirmationModal\')'"
        />
    </form>
</div>
```

### 管理画面での設定

プラグインでCAPTCHAを実装した後、管理者は以下の手順で有効化します：

1. 管理画面 > セキュリティ設定 > CAPTCHA設定 にアクセス
2. 「フォーム別設定」セクションで該当フォームを探す
3. チェックボックスをONにして有効化
4. 設定を保存

**注意:** プラグインのフォームは、管理画面で有効化されるまでCAPTCHA設定のレコードに追加されません。

### CAPTCHAヘルパーメソッド

#### shouldShowCaptcha()

指定したアクションでCAPTCHAを表示すべきかを判定します。

```php
$captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha('dixlase-users.user_register');
// true または false を返す
```

#### renderWidget()

CAPTCHAウィジェットのHTMLを生成します。

```php
$captchaWidget = \App\Helpers\CaptchaHelper::renderWidget('dixlase-users.user_register');
// HTMLコードを返す（Blade内で {!! $captchaWidget !!} として出力）
```

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
