@component('mail::message')
# {{ $toSystem ? 'システムログイン通知' : $member->name . 'さん、ログインがありました' }}

- 日時: {{ $datetime }}
- IPアドレス: {{ $ip }}

@if ($toSystem)
- User-Agent: {{ $ua }}
- メンバーID: {{ $member->id }}
@endif

@endcomponent