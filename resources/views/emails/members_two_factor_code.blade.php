<x-mail::message>
# {{ __('mail.two_factor.email.greeting') }}

{{ __('mail.two_factor.email.message') }}

<x-mail::panel>
<div style="text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #2563eb;">
{{ $code }}
</div>
</x-mail::panel>

{{ __('mail.two_factor.email.instructions') }}

{{ __('mail.two_factor.email.security_notice') }}

{{ __('mail.two_factor.email.regards') }}<br>
{{ $appName }}
</x-mail::message>
