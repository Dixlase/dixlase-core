@extends('layouts.auth')

@section('title', __('two-factor.device.denied_title'))
@section('icon', 'fas fa-times-circle')
@section('header', __('two-factor.device.denied_title'))
@section('description', __('two-factor.device.denied_description'))

@section('content')
<div class="text-center">
    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900 mb-6">
        <i class="fas fa-times text-3xl text-red-600 dark:text-red-400"></i>
    </div>
    
    <p class="text-gray-700 dark:text-gray-300 mb-6">
        {!! __('two-factor.device.denied_message') !!}
    </p>
    
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('two-factor.device.denied_close') }}
    </p>
</div>
@endsection
