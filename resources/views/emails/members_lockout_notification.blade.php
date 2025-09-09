@extends('emails.layout')

@section('content')
<h2>{{ __('mail.lockout_notification.title') }}</h2>

<p>{{ __('mail.lockout_notification.message') }}</p>

<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h3>{{ __('mail.lockout_notification.details') }}</h3>
    <ul style="list-style: none; padding: 0;">
        <li><strong>{{ __('mail.lockout_notification.identifier') }}:</strong> {{ $details['identifier'] }}</li>
        <li><strong>{{ __('mail.lockout_notification.ip_address') }}:</strong> {{ $details['ip_address'] }}</li>
        <li><strong>{{ __('mail.lockout_notification.user_agent') }}:</strong> {{ $details['user_agent'] }}</li>
        <li><strong>{{ __('mail.lockout_notification.timestamp') }}:</strong> {{ $details['timestamp'] }}</li>
    </ul>
    
    <h4>{{ __('mail.lockout_notification.settings') }}</h4>
    <ul style="list-style: none; padding: 0;">
        <li><strong>{{ __('mail.lockout_notification.max_attempts') }}:</strong> {{ $details['max_attempts'] }}{{ __('mail.lockout_notification.times') }}</li>
        <li><strong>{{ __('mail.lockout_notification.time_window') }}:</strong> {{ $details['time_window'] }}{{ __('mail.lockout_notification.minutes') }}</li>
        <li><strong>{{ __('mail.lockout_notification.lockout_duration') }}:</strong> {{ $details['lockout_duration'] }}{{ __('mail.lockout_notification.minutes') }}</li>
    </ul>
</div>

<p>{{ __('mail.lockout_notification.action_required') }}</p>
@endsection
