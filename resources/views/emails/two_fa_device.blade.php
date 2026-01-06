<x-mail::message>
# {{ __('mail.two_fa.device.title') }}

{{ __('mail.two_fa.device.greeting', ['name' => $user->display_name ?? $user->account_name ?? $user->name ?? $user->email]) }}

{{ __('mail.two_fa.device.message') }}

## {{ __('mail.two_fa.details_title') }}

- **{{ __('mail.two_fa.ip_address') }}**: {{ $ipAddress }}
- **{{ __('mail.two_fa.user_agent') }}**: {{ $userAgent }}
- **{{ __('mail.two_fa.timestamp') }}**: {{ $timestamp }}

{{ __('mail.two_fa.device.action_prompt') }}

<x-mail::button :url="$approveUrl" color="success">
{{ __('mail.two_fa.device.approve_button') }}
</x-mail::button>

<x-mail::button :url="$denyUrl" color="error">
{{ __('mail.two_fa.device.deny_button') }}
</x-mail::button>

{{ __('mail.two_fa.security_notice') }}

{{ __('mail.two_fa.regards') }}<br><br>
{{ config('app.name') }}
</x-mail::message>
