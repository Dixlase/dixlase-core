{{--
Admin Pagination Component

Usage:
@include('components.pagination', [
    'pagination' => $pagination,
    'route' => 'admin.settings.systems.logs',
    'routeParams' => ['type' => $logType],
    'mobilePageRange' => 1,
    'desktopPageRange' => 2
])
--}}

@php
    $route = $route ?? 'admin.settings.systems.logs';
    $routeParams = $routeParams ?? [];
    $mobilePageRange = $mobilePageRange ?? 1;
    $desktopPageRange = $desktopPageRange ?? 2;
@endphp

@if(isset($pagination) && $pagination['last_page'] > 1)
    <nav aria-label="{{ __('admin.pagination.navigation') }}" class="mb-4">
        <!-- Mobile Layout: Stack vertically -->
        <div class="flex flex-col space-y-3 md:hidden">
            <!-- Page Info -->
            <div class="text-center">
                <span class="pagination-info text-sm">
                    {{ __('admin.pagination.page', ['current' => $pagination['current_page'], 'total' => $pagination['last_page']]) }}
                </span>
            </div>
            
            <!-- Navigation: Previous/Next + Page Numbers in same row -->
            <div class="flex items-center justify-between gap-2">
                <!-- Previous Button -->
                @if($pagination['prev_page'])
                    <a href="{{ route($route, array_merge($routeParams, ['page' => $pagination['prev_page']])) }}" 
                       class="pagination-button pagination-button--prev flex-shrink-0">
                        {{ __('admin.pagination.previous') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--prev pagination-button--disabled flex-shrink-0">
                        {{ __('admin.pagination.previous') }}
                    </span>
                @endif

                <!-- Page Numbers (Center) -->
                <div class="flex items-center justify-center flex-wrap gap-1 flex-1 min-w-0">
                    @php
                        $start = max(1, $pagination['current_page'] - $mobilePageRange);
                        $end = min($pagination['last_page'], $pagination['current_page'] + $mobilePageRange);
                    @endphp

                    @if($start > 1)
                        <a href="{{ route($route, array_merge($routeParams, ['page' => 1])) }}" 
                           class="pagination-number">1</a>
                        @if($start > 2)
                            <span class="pagination-ellipsis">...</span>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        @if($i == $pagination['current_page'])
                            <span class="pagination-number pagination-number--current" aria-current="page">{{ $i }}</span>
                        @else
                            <a href="{{ route($route, array_merge($routeParams, ['page' => $i])) }}" 
                               class="pagination-number">{{ $i }}</a>
                        @endif
                    @endfor

                    @if($end < $pagination['last_page'])
                        @if($end < $pagination['last_page'] - 1)
                            <span class="pagination-ellipsis">...</span>
                        @endif
                        <a href="{{ route($route, array_merge($routeParams, ['page' => $pagination['last_page']])) }}" 
                           class="pagination-number">{{ $pagination['last_page'] }}</a>
                    @endif
                </div>

                <!-- Next Button -->
                @if($pagination['next_page'])
                    <a href="{{ route($route, array_merge($routeParams, ['page' => $pagination['next_page']])) }}" 
                       class="pagination-button pagination-button--next flex-shrink-0">
                        {{ __('admin.pagination.next') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--next pagination-button--disabled flex-shrink-0">
                        {{ __('admin.pagination.next') }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Desktop Layout: Horizontal -->
        <div class="hidden md:flex justify-between items-center">
            <!-- Left Side: Previous/Next Navigation -->
            <div class="flex items-center space-x-2">
                @if($pagination['prev_page'])
                    <a href="{{ route($route, array_merge($routeParams, ['page' => $pagination['prev_page']])) }}" 
                       class="pagination-button pagination-button--prev">
                        {{ __('admin.pagination.previous') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--prev pagination-button--disabled">
                        {{ __('admin.pagination.previous') }}
                    </span>
                @endif

                <span class="pagination-info">
                    {{ __('admin.pagination.page', ['current' => $pagination['current_page'], 'total' => $pagination['last_page']]) }}
                </span>

                @if($pagination['next_page'])
                    <a href="{{ route($route, array_merge($routeParams, ['page' => $pagination['next_page']])) }}" 
                       class="pagination-button pagination-button--next">
                        {{ __('admin.pagination.next') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--next pagination-button--disabled">
                        {{ __('admin.pagination.next') }}
                    </span>
                @endif
            </div>

            <!-- Right Side: Page Numbers -->
            <div class="flex items-center space-x-1">
                @php
                    $start = max(1, $pagination['current_page'] - $desktopPageRange);
                    $end = min($pagination['last_page'], $pagination['current_page'] + $desktopPageRange);
                @endphp

                @if($start > 1)
                    <a href="{{ route($route, array_merge($routeParams, ['page' => 1])) }}" 
                       class="pagination-number">1</a>
                    @if($start > 2)
                        <span class="pagination-ellipsis">...</span>
                    @endif
                @endif

                @for($i = $start; $i <= $end; $i++)
                    @if($i == $pagination['current_page'])
                        <span class="pagination-number pagination-number--current" aria-current="page">{{ $i }}</span>
                    @else
                        <a href="{{ route($route, array_merge($routeParams, ['page' => $i])) }}" 
                           class="pagination-number">{{ $i }}</a>
                    @endif
                @endfor

                @if($end < $pagination['last_page'])
                    @if($end < $pagination['last_page'] - 1)
                        <span class="pagination-ellipsis">...</span>
                    @endif
                    <a href="{{ route($route, array_merge($routeParams, ['page' => $pagination['last_page']])) }}" 
                       class="pagination-number">{{ $pagination['last_page'] }}</a>
                @endif
            </div>
        </div>
    </nav>
@endif
