@extends('layouts.auth')

@section('title', __('two-factor.device.error_page_title'))
@section('icon', 'fas fa-exclamation-triangle')
@section('header', __('two-factor.device.error_page_title'))
@section('description', __('two-factor.device.error_page_description'))

@section('content')
<div class="text-center">
    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900 mb-6">
        <i class="fas fa-exclamation-triangle text-3xl text-yellow-600 dark:text-yellow-400"></i>
    </div>
    
    <p class="text-gray-700 dark:text-gray-300 mb-6">
        {{ $message ?? __('two-factor.device.error_page_message') }}
    </p>
    
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('two-factor.device.error_page_close') }}
    </p>
</div>
@endsection
