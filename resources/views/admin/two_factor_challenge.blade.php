@extends('layouts.auth')

@section('title', '2段階認証コード入力')

@section('content')
    <x-two-factor-challenge 
        :action="route('admin.two-factor.confirm')"
        :resend-action="route('admin.two-factor.resend')"
        :title="__('auth.two_factor.title')"
        :prompt="__('auth.two_factor.prompt')"
        :submit-text="__('auth.two_factor.submit')"
        :resend-text="__('auth.two_factor.resend')"
        :expire-minutes="config('app.two_factor.email_code_expire')"
        :code-length="6"
        :auto-submit="true"
        :show-expire-time="true"
        :show-resend="true"
    />
@endsection