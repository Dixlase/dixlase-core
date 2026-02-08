<x-mail::message>
# {{ __('mail.two-fa.device.title') }}

{{ __('mail.two-fa.device.greeting', ['name' => $user->display_name ?? $user->account_name ?? $user->name ?? $user->email]) }}

{{ __('mail.two-fa.device.message') }}

## {{ __('mail.two-fa.details_title') }}

- **{{ __('mail.two-fa.ip_address') }}**: {{ $ipAddress }}
- **{{ __('mail.two-fa.user_agent') }}**: {{ $userAgent }}
- **{{ __('mail.two-fa.timestamp') }}**: {{ $timestamp }}

{{ __('mail.two-fa.device.action_prompt') }}

<x-mail::button :url="$approveUrl" color="success">
{{ __('mail.two-fa.device.approve_button') }}
</x-mail::button>

<x-mail::button :url="$denyUrl" color="error">
{{ __('mail.two-fa.device.deny_button') }}
</x-mail::button>

{{ __('mail.two-fa.security_notice') }}

{{ __('mail.two-fa.regards') }}<br><br>
{{ config('app.name') }}
</x-mail::message>
