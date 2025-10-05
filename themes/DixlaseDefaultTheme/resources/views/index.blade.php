@extends('themes::layouts.app')

@section('title', ' - ' . __('Home'))

@section('content')
<div class="container mx-auto px-4 py-16">
    {{-- Hero Section --}}
    <div class="max-w-4xl mx-auto text-center mb-16">
        <h1 class="text-4xl md:text-6xl font-bold mb-6 text-gray-900 dark:text-white">
            {{ __('Welcome to :name', ['name' => config('app.name', 'Dixlase')]) }}
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-8">
            {{ __('Modern CMS Platform for Building Amazing Websites') }}
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <x-front.button variant="primary" size="lg" href="/admin">
                {{ __('Go to Admin') }}
            </x-front.button>
            <x-front.button variant="outline" size="lg" href="#features">
                {{ __('Learn More') }}
            </x-front.button>
        </div>
    </div>

    {{-- Features Section --}}
    <div id="features" class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        {{-- Feature 1: Fast & Modern --}}
        <x-front.card class="hover:shadow-xl transition-shadow duration-300">
            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-3 text-gray-900 dark:text-white">
                    {{ __('Fast & Modern') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ __('Built with modern technologies for optimal performance') }}
                </p>
            </div>
        </x-front.card>

        {{-- Feature 2: Customizable --}}
        <x-front.card class="hover:shadow-xl transition-shadow duration-300">
            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-3 text-gray-900 dark:text-white">
                    {{ __('Customizable') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ __('Easily customize with themes and plugins') }}
                </p>
            </div>
        </x-front.card>

        {{-- Feature 3: Secure --}}
        <x-front.card class="hover:shadow-xl transition-shadow duration-300">
            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-3 text-gray-900 dark:text-white">
                    {{ __('Secure') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ __('Built with security best practices in mind') }}
                </p>
            </div>
        </x-front.card>
    </div>

    {{-- CTA Section --}}
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
            {{ __('Ready to Get Started?') }}
        </h2>
        <p class="text-xl mb-8 text-blue-100">
            {{ __('Create your amazing website with Dixlase today') }}
        </p>
        <x-front.button variant="secondary" size="lg" href="/admin">
            {{ __('Get Started Now') }}
        </x-front.button>
    </div>
</div>
@endsection
