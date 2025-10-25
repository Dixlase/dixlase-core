<x-mail::message>
# {{ __('mail.two_factor.device.title') }}

{{ __('mail.two_factor.device.greeting', ['name' => $member->name]) }}

{{ __('mail.two_factor.device.message') }}

## {{ __('mail.two_factor.device.details_title') }}

- **{{ __('mail.two_factor.device.ip_address') }}**: {{ $ipAddress }}
- **{{ __('mail.two_factor.device.user_agent') }}**: {{ $userAgent }}
- **{{ __('mail.two_factor.device.timestamp') }}**: {{ $timestamp }}

{{ __('mail.two_factor.device.action_prompt') }}

<x-mail::button :url="$approveUrl" color="success">
{{ __('mail.two_factor.device.approve_button') }}
</x-mail::button>

<x-mail::button :url="$denyUrl" color="error">
{{ __('mail.two_factor.device.deny_button') }}
</x-mail::button>

{{ __('mail.two_factor.device.ignore_notice') }}

{{ __('mail.two_factor.device.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
