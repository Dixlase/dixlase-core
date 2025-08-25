@extends('layouts.install')

@section('title', __('install.mail_title'))
@section('header', __('install.mail_header'))
@section('description')
    {!! __('install.mail_description') !!}
@endsection

@section('content')
<form action="{{ route('install.mail.store') }}" method="POST" class="space-y-4">
    @csrf

    @include('components.mail-server-form', [
        'settings' => [],
        'context' => 'install',
        'admin_email' => $admin_email
    ])

    @include('components.mail-test', [
        'context' => 'install',
        'connectionTestRoute' => route('install.mail.test-connection'),
        'mailTestRoute' => route('install.mail.test-send'),
        'showStatus' => false,
        'testStatus' => $testStatus
    ])

    <div class="flex justify-between mt-6">
        <a href="{{ route('install.database') }}" class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600">
            {{ __('install.back') }}
        </a>
        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
            {{ __('install.next') }}
        </button>
    </div>
</form>


@endsection
