@component('mail::message')
# ログイン通知

{{ $toSystem ? 'システム通知' : $member->name . 'さん、ログインがありました。' }}

- 日時: {{ $datetime }}
- IPアドレス: {{ $ip }}

@if ($toSystem)
- User-Agent: {{ $ua }}
- メンバーID: {{ $member->id }}
@endif

@endcomponent