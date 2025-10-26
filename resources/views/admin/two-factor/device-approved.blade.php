@extends('layouts.auth')

@section('title', __('two-factor.device.approved_title'))
@section('icon', 'fas fa-check-circle')
@section('header', __('two-factor.device.approved_title'))
@section('description', __('two-factor.device.approved_description'))

@section('content')
<div class="text-center">
    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900 mb-6">
        <i class="fas fa-check text-3xl text-green-600 dark:text-green-400"></i>
    </div>
    
    <p class="text-gray-700 dark:text-gray-300 mb-6">
        {!! __('two-factor.device.approved_message') !!}
    </p>
    
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('two-factor.device.approved_close') }}
    </p>
</div>
@endsection
