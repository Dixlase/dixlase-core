<x-mail::message>
# {{ __('mail.two_fa.email.greeting') }}

{{ __('mail.two_fa.email.message') }}

<x-mail::panel>
<div style="text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #2563eb;">
{{ $code }}
</div>
</x-mail::panel>

{{ __('mail.two_fa.email.instructions') }}

{{ __('mail.two_fa.security_notice') }}

{{ __('mail.two_fa.regards') }}<br><br>
{{ $appName ?? config('app.name') }}
</x-mail::message>
