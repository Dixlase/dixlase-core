{{-- パーシャル用変数のデフォルト値設定 --}}
@php
    $methods = $methods ?? [];
    $currentMethod = $currentMethod ?? null;
    $context = $context ?? 'admin';
    $showRecoveryCode = $showRecoveryCode ?? true;
    $recoveryCodeRoute = $recoveryCodeRoute ?? null; // コントローラーから渡される
@endphp
<p class="my-4 text-center text-gray-800 dark:text-white">{{ __('two_fa.switch_method_prompt') }}</p>
<div class="mt-4 text-center space-y-2">
    @php
        // 利用可能な認証方法（現在の方法を除く）
        $availableMethods = collect($methods)->filter(fn($method) => $method['value'] !== $currentMethod);
    @endphp
    
    @if($availableMethods->isNotEmpty())
        @foreach($availableMethods as $method)
            <div>
                <a href="{{ $method['url'] }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    @if($method['value'] === 0)
                        <i class="fas fa-envelope mr-1"></i>
                    @elseif($method['value'] === 1)
                        <i class="fas fa-key mr-1"></i>
                    @endif
                    {{ $method['label'] }}
                </a>
            </div>
        @endforeach
    @endif
    
    @if($showRecoveryCode && $recoveryCodeRoute)
        <div>
            <a href="{{ route($recoveryCodeRoute) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                <i class="fas fa-life-ring mr-1"></i>{{ __('two_fa.recovery_code.use_recovery_code') }}
            </a>
        </div>
    @endif
</div>
