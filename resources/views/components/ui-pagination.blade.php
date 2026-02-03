{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@php
    $route = $route ?? 'admin.settings.systems.logs';
    $routeParams = $routeParams ?? [];
    $mobilePageRange = $mobilePageRange ?? 1;
    $desktopPageRange = $desktopPageRange ?? 2;
@endphp

@if(isset($pagination) && $pagination['last_page'] > 1)
    <nav aria-label="{{ __('components.pagination.navigation') }}" class="mb-4">
        <!-- Mobile Layout: Stack vertically -->
        <div class="flex flex-col space-y-3 md:hidden">
            <!-- Page Info -->
            <div class="text-center">
                <span class="pagination-info text-sm">
                    {{ __('components.pagination.page', ['current' => $pagination['current_page'], 'total' => $pagination['last_page']]) }}
                </span>
            </div>
            
            <!-- Navigation: Previous/Next + Page Numbers in same row -->
            <div class="flex items-center justify-between gap-2">
                <!-- Previous Button -->
                @if($pagination['prev_page'])
                    <a href="{{ route($route, array_merge($routeParams, ['page' => $pagination['prev_page']])) }}" 
                       class="pagination-button pagination-button--prev flex-shrink-0">
                        {{ __('components.pagination.previous') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--prev pagination-button--disabled flex-shrink-0">
                        {{ __('components.pagination.previous') }}
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
                        {{ __('components.pagination.next') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--next pagination-button--disabled flex-shrink-0">
                        {{ __('components.pagination.next') }}
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
                        {{ __('components.pagination.previous') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--prev pagination-button--disabled">
                        {{ __('components.pagination.previous') }}
                    </span>
                @endif

                <span class="pagination-info">
                    {{ __('components.pagination.page', ['current' => $pagination['current_page'], 'total' => $pagination['last_page']]) }}
                </span>

                @if($pagination['next_page'])
                    <a href="{{ route($route, array_merge($routeParams, ['page' => $pagination['next_page']])) }}" 
                       class="pagination-button pagination-button--next">
                        {{ __('components.pagination.next') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--next pagination-button--disabled">
                        {{ __('components.pagination.next') }}
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
