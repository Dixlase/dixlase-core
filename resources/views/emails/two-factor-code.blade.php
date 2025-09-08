<x-mail::message>
# {{ __('mail.two_factor.default.greeting') }}

{{ __('mail.two_factor.default.message') }}

<x-mail::panel>
<div style="text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #2563eb;">
{{ $code }}
</div>
</x-mail::panel>

{{ __('mail.two_factor.default.instructions') }}

{{ __('mail.two_factor.default.security_notice') }}

{{ __('mail.two_factor.default.regards') }}<br>
{{ $appName }}
</x-mail::message>
