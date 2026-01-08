<x-mail::message>
# {{ __('mail.lockout_notification.title') }}

{{ __('mail.lockout_notification.message') }}

**{{ __('mail.lockout_notification.details') }}**

- **{{ __('mail.lockout_notification.identifier') }}:** {{ $details['identifier'] }}
- **{{ __('mail.lockout_notification.ip_address') }}:** {{ $details['ip_address'] }}
- **{{ __('mail.lockout_notification.user_agent') }}:** {{ $details['user_agent'] }}
- **{{ __('mail.lockout_notification.timestamp') }}:** {{ $details['timestamp'] }}

**{{ __('mail.lockout_notification.settings') }}**

- **{{ __('mail.lockout_notification.max_attempts') }}:** {{ $details['max_attempts'] }}{{ __('mail.lockout_notification.times') }}
- **{{ __('mail.lockout_notification.time_window') }}:** {{ $details['time_window'] }}{{ __('mail.lockout_notification.minutes') }}
- **{{ __('mail.lockout_notification.lockout_duration') }}:** {{ $details['lockout_duration'] }}{{ __('mail.lockout_notification.minutes') }}

{{ __('mail.lockout_notification.action_required') }}

{{ __('mail.lockout_notification.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
