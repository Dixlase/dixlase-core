<x-mail::message>
# {{ __('mail.two_factor.admin.greeting') }}

{{ __('mail.two_factor.admin.message') }}

<x-mail::panel>
<div style="text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #2563eb;">
{{ $code }}
</div>
</x-mail::panel>

{{ __('mail.two_factor.admin.instructions') }}

{{ __('mail.two_factor.admin.security_notice') }}

{{ __('mail.two_factor.admin.regards') }}<br>
{{ $appName }}
</x-mail::message>
