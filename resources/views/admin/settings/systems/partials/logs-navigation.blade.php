{{--
    ログナビゲーションパーシャル
    
    @param string $logType - 現在のログタイプ
    @param string|null $currentView - 監査ログの現在のビュー（'db' or 'file'）
--}}

@php
    $currentView = $currentView ?? request('view', 'db');
@endphp

<!-- Log Type Selection -->
<div class="mb-4">
    <h2>{{ __('admin.settings.systems.logs.log_type_label') }}</h2>
    
    <!-- Admin Logs -->
    <div class="mb-4">
        <h3 class="mb-2 md:mb-0 md:mr-2 md:inline-block text-center md:text-left">{{ __('common.admin_logs') }}</h3>
        <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
            @foreach (['activity', 'error', 'dixlase'] as $type)
                <a href="{{ route('admin.settings.systems.logs', ['type' => $type]) }}"
                    @class([
                        'nav-button',
                        'nav-button--blue',
                        'nav-button--active' => $logType === $type
                    ])>
                    @if($type === 'error')
                        {{ __('common.error_log') }}
                    @else
                        {{ __('admin.settings.systems.logs.' . $type) }}
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
    
    <!-- Front Logs -->
    <div class="mb-4">
        <h3 class="mb-2 md:mb-0 md:mr-2 md:inline-block text-center md:text-left">{{ __('common.front_logs') }}</h3>
        <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
            @foreach (['front_activity', 'front_error'] as $type)
                <a href="{{ route('admin.settings.systems.logs', ['type' => $type]) }}"
                    @class([
                        'nav-button',
                        'nav-button--blue',
                        'nav-button--active' => $logType === $type
                    ])>
                    {{ __('admin.settings.systems.logs.' . $type) }}
                </a>
            @endforeach
        </nav>
    </div>
    
    <!-- Security Logs -->
    <div class="mb-4">
        <h3 class="mb-2 md:mb-0 md:mr-2 md:inline-block text-center md:text-left">{{ __('admin.settings.systems.logs.security_logs_label') }}</h3>
        <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
            <a href="{{ route('admin.settings.systems.logs', ['type' => 'csp']) }}"
                @class([
                    'nav-button',
                    'nav-button--blue',
                    'nav-button--active' => $logType === 'csp'
                ])>
                {{ __('admin.settings.systems.logs.csp') }}
            </a>
        </nav>
    </div>

    <!-- Audit Logs -->
    <div class="mb-4">
        <h3 class="mb-2 md:mb-0 md:mr-2 md:inline-block text-center md:text-left">{{ __('admin.settings.audit_logs.title') }}</h3>
        <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
            <a href="{{ route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db']) }}"
                @class([
                    'nav-button',
                    'nav-button--blue',
                    'nav-button--active' => $logType === 'audit' && $currentView === 'db'
                ])>
                {{ __('admin.settings.systems.logs.audit_db') }}
            </a>
            <a href="{{ route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'file']) }}"
                @class([
                    'nav-button',
                    'nav-button--blue',
                    'nav-button--active' => $logType === 'audit' && $currentView === 'file'
                ])>
                {{ __('admin.settings.systems.logs.audit_file') }}
            </a>
        </nav>
    </div>

    
</div>
