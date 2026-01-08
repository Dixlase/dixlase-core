<x-mail::message>
# {{ __('mail.login_notification.title') }}

{{ $toSystem ? __('mail.login_notification.system_message') : __('mail.login_notification.user_message', ['name' => $member->display_name ?? $member->account_name]) }}

**{{ __('mail.login_notification.details_title') }}**

- **{{ __('mail.login_notification.datetime') }}** {{ $datetime }}
- **{{ __('mail.login_notification.ip_address') }}** {{ $ip }}

@if ($toSystem)
- **{{ __('mail.login_notification.user_agent') }}** {{ $ua }}
- **{{ __('mail.login_notification.member_id') }}** {{ $member->id }}
@endif

@if (!$toSystem)
{{ __('mail.login_notification.security_notice') }}

<x-mail::button :url="config('app.url')" color="primary">
{{ __('mail.login_notification.access_site') }}
</x-mail::button>
@endif

{{ __('mail.login_notification.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>