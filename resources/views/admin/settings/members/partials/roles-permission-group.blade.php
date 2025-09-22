{{--
Roles Permission Group Component with Accordion

Usage:
@include('admin.settings.members.partials.roles-permission-group', [
    'sectionTitle' => 'セクション名',
    'sectionId' => 'section_id',
    'items' => $items,
    'permissions' => $permissions,
    'roles' => $roles
])
--}}

<div class="accordion-section bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm"
     x-init="
        if (typeof openSections['{{ $sectionId }}'] === 'undefined') {
            openSections['{{ $sectionId }}'] = false;
            console.log('Initialized section: {{ $sectionId }}', openSections);
        }
     ">
    {{-- アコーディオンヘッダー --}}
    <button 
        type="button"
        class="accordion-header w-full px-6 py-6 text-left flex items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        @click="openSections['{{ $sectionId }}'] = !openSections['{{ $sectionId }}']"
        :class="{ 
            'border-b border-gray-200 dark:border-gray-600 rounded-t-lg rounded-b-none': openSections['{{ $sectionId }}']
        }"
    >
        {{-- 左側の開閉アイコン --}}
        <i class="mb-2 fas fa-chevron-right w-2 h-2 text-gray-500 dark:text-gray-400 transition-transform duration-300 mr-3 flex-shrink-0"
           :class="{ 'rotate-90': openSections['{{ $sectionId }}'] }"
           style="transform-origin: center;"></i>
        
        <h2 class="my-2 text-md font-bold text-gray-900 dark:text-white flex-grow">
            {{ $sectionTitle }}
        </h2>
    </button>
    
    {{-- アコーディオンコンテンツ --}}
    <div 
        class="accordion-content overflow-hidden rounded-b-lg"
        x-show="openSections['{{ $sectionId }}']"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100 max-h-screen"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 max-h-screen"
        x-transition:leave-end="opacity-0 max-h-0"
    >
        <div class="p-6 space-y-6">
            @foreach ($items as $item)
                @php
                    $accessRoles = $permissions[$item['menuKey']]->access_roles ?? [];
                    $viewRoles = $permissions[$item['menuKey']]->view_roles ?? [];
                @endphp

                <section class="permission-group bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-6">
                    <header class="permission-group__header mb-6">
                        <h3 class="permission-group__title text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $item['title'] }}
                            <span class="permission-group__key text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                                ({{ $item['menuKey'] }})
                            </span>
                        </h3>
                    </header>

                    <div class="permission-group__content grid md:grid-cols-2 gap-8">
                        <!-- Access Permissions -->
                        <fieldset class="permission-section">
                            <legend class="permission-section__title text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('admin.settings.members.roles.access_roles') }}
                            </legend>
                            
                            <div class="permission-section__options space-y-2" role="group" aria-labelledby="access-roles-{{ $item['menuKey'] }}">
                                @php
                                    $accessOptions = [];
                                    foreach ($roles as $role) {
                                        if ($role->value !== \App\Enums\MemberRole::SUPER_ADMIN->value) {
                                            $accessOptions[$role->value] = $role->label();
                                        }
                                    }
                                @endphp
                                
                                @include('components.form.checkbox-group', [
                                    'name' => "permissions[{$item['menuKey']}][access_roles]",
                                    'options' => $accessOptions,
                                    'values' => $accessRoles,
                                    'flexDirection' => 'col',
                                    'permissionStyle' => true,
                                    'class' => 'permission-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700'
                                ])
                            </div>
                        </fieldset>

                        <!-- View Permissions -->
                        <fieldset class="permission-section">
                            <legend class="permission-section__title text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('admin.settings.members.roles.view_roles') }}
                            </legend>
                            
                            <div class="permission-section__options space-y-2" role="group" aria-labelledby="view-roles-{{ $item['menuKey'] }}">
                                @php
                                    $viewOptions = [];
                                    foreach ($roles as $role) {
                                        if ($role->value !== \App\Enums\MemberRole::SUPER_ADMIN->value) {
                                            $viewOptions[$role->value] = $role->label();
                                        }
                                    }
                                @endphp
                                
                                @include('components.form.checkbox-group', [
                                    'name' => "permissions[{$item['menuKey']}][view_roles]",
                                    'options' => $viewOptions,
                                    'values' => $viewRoles,
                                    'flexDirection' => 'col',
                                    'permissionStyle' => true,
                                    'class' => 'permission-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700'
                                ])
                            </div>
                        </fieldset>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
