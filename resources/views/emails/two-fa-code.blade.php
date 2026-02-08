<x-mail::message>
# {{ __('mail.two-fa.email.greeting') }}

{{ __('mail.two-fa.email.message') }}

<x-mail::panel>
<div style="text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #2563eb;">
{{ $code }}
</div>
</x-mail::panel>

{{ __('mail.two-fa.email.instructions') }}

{{ __('mail.two-fa.security_notice') }}

{{ __('mail.two-fa.regards') }}<br><br>
{{ $appName ?? config('app.name') }}
</x-mail::message>
