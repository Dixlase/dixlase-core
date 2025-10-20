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

@props([
    'action',
    'emailLabel',
    'submitText',
    'backText' => null,
    'backUrl' => null
])

<form method="POST" action="{{ $action }}">
    @csrf
    
    <section>
        <fieldset>
            <legend class="sr-only">{{ $emailLabel }}</legend>
            
            @include('components::form.text', [
                'type' => 'email',
                'id' => 'email',
                'name' => 'email',
                'value' => old('email'),
                'required' => true,
                'autocomplete' => 'email',
                'ariaLabel' => $emailLabel,
                'placeholder' => $emailLabel
            ])
            
            @include('components::form.error', [
                'messages' => $errors->get('email')
            ])
        </fieldset>
    </section>

    <section class="flex items-center flex-col justify-between mt-6">
        @include('components::form.button', [
            'type' => 'submit',
            'variant' => 'primary',
            'size' => 'md',
            'label' => $submitText,
            'class' => 'w-full'
        ])

        @if($backText && $backUrl)
            <a class="mt-4 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:underline" 
               href="{{ $backUrl }}">
                {{ $backText }}
            </a>
        @endif
    </section>
</form>
