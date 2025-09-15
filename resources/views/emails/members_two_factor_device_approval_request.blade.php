<x-mail::message>
# {{ __('mail.device_auth.greeting') }}

{{ __('mail.device_auth.new_device_detected') }}

<x-mail::panel>
<strong>{{ __('mail.device_auth.device_info') }}</strong><br>
{{ __('mail.device_auth.device_name') }}: {{ $deviceName }}<br>
{{ __('mail.device_auth.ip_address') }}: {{ $ipAddress }}<br>
{{ __('mail.device_auth.location') }}: {{ $location ?? __('mail.device_auth.unknown_location') }}<br>
{{ __('mail.device_auth.time') }}: {{ $timestamp }}
</x-mail::panel>

{{ __('mail.device_auth.approval_message') }}

<x-mail::button :url="$approvalUrl" color="success">
{{ __('mail.device_auth.approve_button') }}
</x-mail::button>

{{ __('mail.device_auth.manual_approval') }}

<x-mail::panel>
<div style="text-align: center; font-size: 18px; font-weight: bold; letter-spacing: 2px; color: #059669;">
{{ $approvalCode }}
</div>
</x-mail::panel>

{{ __('mail.device_auth.security_notice') }}

<x-mail::button :url="$denyUrl" color="error">
{{ __('mail.device_auth.deny_button') }}
</x-mail::button>

{{ __('mail.device_auth.ignore_message') }}

{{ __('mail.device_auth.regards') }}<br>
{{ $appName }}
</x-mail::message>
