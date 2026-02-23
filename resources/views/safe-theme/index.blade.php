{{--
Safe Theme Index - セーフモード用の最小限フロントページ
--}}

@extends('themes::layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-3xl mx-auto">
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-6">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-paint-brush text-purple-600 dark:text-purple-400 text-xl mt-0.5"></i>
                    <div>
                        <h2 class="text-lg font-semibold text-purple-800 dark:text-purple-200">
                            {{ __('admin/safe-mode.theme_safe_mode_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-purple-700 dark:text-purple-300">
                            {{ __('admin/safe-mode.theme_safe_mode_front_message') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
