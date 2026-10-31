{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes via @extends('layouts.admin')

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', env('APP_LOCALE', config('app.locale', 'en'))) }}"
    class="{{ $htmlClass ?? '' }}"
    x-data="appearanceMode('{{ $appearance }}')"
    x-init="init()"
    :class="{ 'dark': isDark, 'light': !isDark, 'theme-ready': themeReady }"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Favicon links. See layouts/auth.blade.php for the rationale;
             kept in lockstep so the admin tab icon matches the login
             page's. --}}
        <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon-16.png') }}">

        {{-- Prevent FOUC: Apply dark mode class immediately before CSS and Alpine.js load --}}
        <script @cspNonce>
            (function(){
                var a='{{ $appearance }}';
                var d=a==='2'||(a==='0'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.add(d?'dark':'light');
            })();
        </script>
        {{-- Propagate banner stack height to offset of admin bar, sidebar, and main content --}}
        <style @cspNonce>
            :root { --admin-banner-offset: 0px; }
            #admin-bar { top: var(--admin-banner-offset, 0px); }
            #admin-layout-flex { padding-top: calc(3rem + var(--admin-banner-offset, 0px)); }
            @media(min-width:768px){
                #admin-layout-flex > aside { top: calc(3rem + var(--admin-banner-offset, 0px)); }
            }
            #admin-sidebar-toggle { top: calc(3.5rem + var(--admin-banner-offset, 0px)); }
            /* Mobile sidebar tab (the ">" handle inside the mobile drawer): its
               mt-[56px] must also clear the demo banner or it overlaps the banner.
               Scoped by the drawer id so the desktop aside's hidden copy is untouched. */
            #admin-mobile-sidebar .js-mobile-sidebar-tab { margin-top: calc(3.5rem + var(--admin-banner-offset, 0px)); }
            #admin-right-sidebar-toggle { top: calc(3.5rem + var(--admin-banner-offset, 0px)); }
            #admin-right-sidebar-panel { top: calc(3rem + var(--admin-banner-offset, 0px)); }
            .modal { padding-top: var(--admin-banner-offset, 0px); }
            .modal .modal-container { max-height: calc(100vh - 6rem - var(--admin-banner-offset, 0px)); }
        </style>
        {{-- Prevent FOUC: decide the sidebar's shape before the first paint.
             The stylesheet owns every width and inset from this one class
             (see resources/src/admin/scss/_sidebar-modes.scss), so the only
             thing that cannot wait for Alpine is the class itself. Keep this
             in step with sidebarBandFor() / readStoredRail() in
             resources/src/admin/js/layout.js. --}}
        <script @cspNonce>
            (function(){
                var w=window.innerWidth,rail=false;
                if(w>=1280){
                    var m=null;
                    try{m=localStorage.getItem('adminSidebarMode');}catch(e){}
                    if(m===null){
                        try{m=localStorage.getItem('sidebarCollapsed')==='true'?'rail':'expanded';}catch(e){m='expanded';}
                    }
                    rail=m==='rail';
                }else if(w>=640){
                    rail=true;
                }
                if(rail){document.documentElement.classList.add('dls-sidebar-rail-active');}
            })();
        </script>

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- アセットを読み込み -->
        {!! load_active_assets() !!}

        {{-- Notification component (load before other scripts) --}}
        <x-ui-notification />

    </head>
    <body class="admin font-sans antialiased transition-colors-unified dark:bg-black dark:text-white"
          data-default-appearance="{{ $appearance }}"
          x-data="adminLayout()"
          x-init="init()">
        <div class="min-h-screen">
            {{-- Admin panel banner stack (maintenance / safe mode / system warnings) --}}
            <div id="admin-banner-stack"
                 class="fixed top-0 left-0 right-0 z-[9999] flex flex-col"
                 x-data
                 x-init="
                     const root = document.documentElement;
                     const update = () => {
                         const h = $el.offsetHeight || 0;
                         root.style.setProperty('--admin-banner-offset', h + 'px');
                     };
                     update();
                     const ro = new ResizeObserver(update);
                     ro.observe($el);
                     window.addEventListener('resize', update);
                 ">
                <x-ui-admin-demo-banner />
                <x-ui-admin-maintenance-banner />
                <x-security.safe-mode-banner />
                <x-ui-system-banner />
            </div>

            <!-- Admin Bar (Header) -->
            <x-ui-admin-bar :isAdminLayout="true" />

            <div id="admin-layout-flex" class="min-h-screen flex relative">
                <!-- Navigation Sidebar (Desktop only) -->
                <aside id="admin-sidebar"
                       class="md:fixed md:top-12 md:bottom-0 hidden sm:block dls-admin-sidebar z-40 flex-shrink-0 border-gray-300 @if($transitionEnabled ?? false) transition-all duration-[300ms] @else transition-[width] duration-300 @endif"
                       role="navigation" aria-label="Main navigation">
                    @include('admin.partials.sidebar', [
                        'transitionEnabled' => $transitionEnabled ?? null,
                        'route_name' => Route::currentRouteName()
                    ])
                </aside>

                {{-- Sidebar Toggle Button (Desktop / Tablet).
                     Three widths, narrowest where the pointer is most
                     precise: lg+ (mouse-primary desktops) is ~22px
                     (lg:px-1, lg:text-xs chevron); the sm/md tablet range
                     is ~32px (px-2, text-sm), wide enough for a thumb
                     without dominating the page header; the phone drawer
                     tab in partials/sidebar.blade.php stays at the 44px
                     touch target, since this button is hidden under sm.
                     The page heading's sm:pl-5 keeps clear of the tab
                     (see the header below). bg-*/90 (was /75) keeps the
                     tab legible against busy page backgrounds. --}}
                <button type="button"
                        id="admin-sidebar-toggle"
                        @click="toggleSidebar()"
                        class="hidden sm:flex fixed top-14 left-0 z-40 items-center justify-center backdrop-blur-sm dark:bg-gray-900/90 bg-white/90 text-blue-400 dark:text-white px-2 py-4 lg:px-1 rounded-r-lg shadow-md border border-l-0 border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                        :style="sidebarReady ? 'transition: transform 300ms ease-in-out' : ''"
                        aria-controls="admin-sidebar"
                        :aria-expanded="(! sidebarRailActive()).toString()"
                        {{-- The two labels are handed over as data attributes
                             rather than quoted inside the expression: a
                             translation may contain either quote character,
                             and Blade's escaping only protects the attribute
                             when the text is the whole value. --}}
                        data-label-expand="{{ __('admin/navigation.show_labels') }}"
                        data-label-collapse="{{ __('admin/navigation.show_icons_only') }}"
                        :aria-label="sidebarRailActive() ? $el.dataset.labelExpand : $el.dataset.labelCollapse">
                    <i class="fas text-sm lg:text-xs" :class="sidebarRailActive() ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                </button>

                {{-- Backdrop for the tablet reveal. Between 640px and 1279px
                     the full menu comes out over the content rather than
                     moving it, so it needs somewhere to click to dismiss it.
                     Hidden from 1280px up, where the menu pushes instead and
                     nothing is covered. --}}
                <div x-show="sidebarOverlayOpen()"
                     x-cloak
                     @click="closeSidebarOverlay()"
                     @keydown.escape.window="closeSidebarOverlay()"
                     class="hidden sm:block xl:hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-[35]"
                     aria-hidden="true"></div>

                {{-- Main Content Area.
                     No left padding on <main> itself: the page header
                     (which owns the border-bottom under the heading)
                     must start exactly at the sidebar edge (open) or the
                     viewport edge (collapsed) so that border runs
                     unbroken to the edge. Horizontal breathing room is
                     provided by each section's own px-6 lg:px-8 and by
                     the header's px-8, not by a wrapper gap. --}}
                <main @right-sidebar-active.window="rightSidebarActive = true"
                      id="admin-main-content"
                      class="ml-0 flex-1 bg-white text-gray-900 dark:bg-black dark:text-white"
                      :class="{
                          'lg:mr-80': rightSidebarActive && !rightSidebarCollapsed
                      }"
                      x-init="
                          (() => {
                              let isAppearancePage = @if($transitionEnabled ?? false) true @else false @endif;
                              
                              // 初回表示後にトランジションを追加（全ページ共通）
                              setTimeout(() => {
                                  // transition-colorsを削除してからtransition-allを追加
                                  $el.classList.remove('transition-colors');
                                  
                                  if (isAppearancePage) {
                                      // 外観設定ページ: 外観モード切り替え用のトランジション（300ms）
                                      $el.classList.add('transition-all', 'duration-300');
                                      $el.style.transitionProperty = 'all';
                                      $el.style.transitionDuration = '300ms';
                                      $el.style.transitionTimingFunction = 'cubic-bezier(0.4, 0, 0.2, 1)';
                                  } else {
                                      // 他のページ: サイドバートグル用のトランジション（margin のみ）
                                      $el.style.transitionProperty = 'margin-left, margin-right';
                                      $el.style.transitionDuration = '300ms';
                                      $el.style.transitionTimingFunction = 'cubic-bezier(0.4, 0, 0.2, 1)';
                                  }
                              }, 100);
                              
                              // transition-colorsが追加されたら即座に削除（外観モード切り替え時の対策）
                              const observer = new MutationObserver((mutations) => {
                                  mutations.forEach((mutation) => {
                                      if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                          if ($el.classList.contains('transition-colors') && $el.classList.contains('transition-all')) {
                                              $el.classList.remove('transition-colors');
                                          }
                                      }
                                  });
                              });
                              
                              observer.observe($el, {
                                  attributes: true,
                                  attributeFilter: ['class']
                              });
                          })();
                      "
                      role="main">

                    <!-- Page Header -->
                    {{-- The header's own padding (px-8) is a protected offset —
                         it anchors every admin page horizontally. The extra
                         left indent lives on the <h1> and only exists to
                         keep the heading clear of the sidebar toggle tab
                         that overlaps this row:
                           - below sm: pl-8 (32px) — the 44px mobile drawer
                             tab sits at x=0, heading lands at 64px.
                           - sm..lg: sm:pl-5 (20px) — the ~32px tablet
                             tab docks to the sidebar edge (open) or x=0
                             (collapsed); the heading clears it.
                           - lg+: lg:pl-0 — the slim desktop tab needs no
                             extra clearance, so the original indent is
                             restored. --}}
                    <header class="mx-auto pt-6 pb-6 px-8 bg-white text-gray-800 border-b border-gray-300 dark:border-gray-700 dark:bg-black dark:text-white @if($transitionEnabled ?? false) transition-colors duration-[500ms] @endif">
                        <h1 class="font-semibold text-xl leading-tight text-gray-800 dark:text-white pl-8 sm:pl-5 lg:pl-0">
                            {{ __($heading ?? '') }}
                        </h1>
                    </header>

                    <!-- Breadcrumbs -->
                    @if(!empty($breadcrumbs) && count($breadcrumbs) > 0)
                        <nav class="w-full px-6 lg:px-8 mt-2">
                            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                @foreach($breadcrumbs as $index => $breadcrumb)
                                    @if($index > 0)
                                        <li><i class="fas fa-chevron-right text-xs"></i></li>
                                    @endif
                                    <li>
                                        @if(!empty($breadcrumb['route']))
                                            <a href="{{ route($breadcrumb['route'], $breadcrumb['params'] ?? []) }}" class="{{ $loop->last ? 'text-gray-900 dark:text-white font-medium' : 'hover:text-gray-700 dark:hover:text-white' }}">
                                                {{ $breadcrumb['label'] }}
                                            </a>
                                        @else
                                            <span class="{{ $loop->last ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-500 dark:text-gray-400' }}">{{ $breadcrumb['label'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    <!-- Page Description -->
                    @if(!empty($description))
                        <div class="w-full px-6 lg:px-8 mt-1">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $description }}
                            </p>
                        </div>
                    @endif

                    <!-- Page Content -->
                    <article class="w-full px-6 lg:px-8 pb-8 mt-5">
                        <x-ui-flash-message />
                        {{-- View-only notice: shown when the member may view but
                             not edit this page. `menuEditable` is shared by
                             CheckMenuAccess (core) or a plugin controller; the
                             `?? true` default keeps ordinary pages unaffected. --}}
                        @if(! ($menuEditable ?? true))
                            <div class="mb-5">
                                <x-ui-message type="info" :message="__('common.view_only_page_notice')" />
                            </div>
                        @endif
                        @yield('content')
                    </article>

                    {{-- Sticky save footer: rendered whenever the page
                         declares a @section('save'). The button inside
                         is responsible for dimming / disabling itself
                         when the current user has view-only permission
                         (x-admin.save-button reads menuEditable), so
                         the footer stripe is always shown and page
                         height does not shift between menus. --}}
                    @hasSection('save')
                        <div class="sticky bottom-0 z-30 backdrop-blur-sm bg-white/75 dark:bg-gray-900/75 border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3 @if($transitionEnabled ?? false) transition-colors duration-[0ms] @endif">
                            <div class="w-full mx-auto flex justify-center">
                                @yield('save')
                            </div>
                        </div>
                    @endif

                {{-- Right sidebar overlay on mobile --}}
                <div x-show="!rightSidebarCollapsed"
                     @click="rightSidebarCollapsed = true"
                     x-cloak
                     class="fixed inset-0 bg-black/50 dark:bg-black/60 z-40 lg:hidden"
                     aria-hidden="true"></div>

                </main>
            </div>
            <!-- Footer -->
            @include('admin.partials.footer')
        </div>

        <!-- Modals Section -->
        @hasSection('modals')
            @yield('modals')
        @endif
        @stack('modals')

        {{-- Common modal for CSRF session expiration (opens automatically when fetch returns 419) --}}
        <script @cspNonce>
            window.csrfErrorTranslations = {
                title: @json(__('common.csrf.title')),
                message: @json(__('common.csrf.message')),
                reload: @json(__('common.csrf.reload')),
            };
        </script>
        <x-ui-modal
            id="csrfErrorModal"
            :title="__('common.csrf.title')"
            iconType="warning"
            :dismissible="false"
        >
            <p class="text-sm text-gray-700 dark:text-gray-300 text-center">{{ __('common.csrf.message') }}</p>
            <x-slot:footer>
                <x-form-button
                    type="button"
                    variant="primary"
                    icon="fas fa-sync-alt"
                    :label="__('common.csrf.reload')"
                    id="csrfErrorReloadBtn"
                />
            </x-slot:footer>
        </x-ui-modal>
        <script @cspNonce>
            document.addEventListener('DOMContentLoaded', function () {
                const btn = document.getElementById('csrfErrorReloadBtn');
                if (btn) {
                    btn.addEventListener('click', function () {
                        window.location.reload();
                    });
                }
            });
        </script>

        {{-- Page-specific styles --}}
        @hasSection('styles')
            @yield('styles')
        @endif
        
        {{-- Component styles from @push --}}
        @stack('styles')

        {{-- Page-specific scripts --}}
        @hasSection('scripts')
            @yield('scripts')
        @endif

        @stack('scripts')

        {{-- Demo mode: render forms targeting DemoGuard-blocked routes as
             visually read-only. No-op outside demo mode / for super admins. --}}
        <x-ui-demo-readonly-guard />

    </body>
</html>
