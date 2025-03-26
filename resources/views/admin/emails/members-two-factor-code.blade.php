@component('mail::message')
# ログイン認証コード

以下の認証コードをログイン画面で入力してください：

@component('mail::panel')
{{ $code }}
@endcomponent

このコードは **{{ config('members.two_factor.email_code_expire') }}分間** 有効です。<br>
誰かが意図せずこのコードを取得していた場合は、速やかにご連絡ください。

---

※このメールはシステムから自動送信されています。

@endcomponent