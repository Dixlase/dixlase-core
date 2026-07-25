{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

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

@extends('layouts.admin')

@section('content')
    <form id="media-settings-form" action="{{ route('admin.media.settings.update') }}" method="POST" 
          x-data="{ 
              showZipSettings: {{ ($securitySettings['zip_security_enabled'] ?? true) ? 'true' : 'false' }},
              riskyTypes: {
                  svg: {{ in_array('svg', $allowedFileTypes) ? 'true' : 'false' }},
                  zip: {{ in_array('zip', $allowedFileTypes) ? 'true' : 'false' }},
                  pdf: {{ in_array('pdf', $allowedFileTypes) ? 'true' : 'false' }},
                  docx: {{ in_array('docx', $allowedFileTypes) ? 'true' : 'false' }},
                  tex: {{ in_array('tex', $allowedFileTypes) ? 'true' : 'false' }}
              },
              get hasRiskyTypes() {
                  return this.riskyTypes.svg || this.riskyTypes.zip || this.riskyTypes.pdf || this.riskyTypes.docx || this.riskyTypes.tex;
              },
              updateRiskyType(ext, checked) {
                  if (this.riskyTypes.hasOwnProperty(ext)) {
                      this.riskyTypes[ext] = checked;
                  }
              }
          }">
        @csrf

        {{-- Allowed file types --}}
        <div class="mb-6">
            <h2>{{ __('admin/media/settings.allowed_file_types') }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($fileExtensions as $extension)
                    <div class="flex items-center justify-start bg-gray-100 dark:bg-gray-700 p-3 rounded-lg shadow-sm">
                        <label for="file-type-{{ $extension }}" class="flex items-center cursor-pointer w-full">
                            <div class="relative inline-flex items-center flex-shrink-0">
                                <input type="checkbox" 
                                       id="file-type-{{ $extension }}"
                                       name="allowed_file_types[]"
                                       value="{{ $extension }}"
                                       class="sr-only peer"
                                       {{ in_array($extension, $allowedFileTypes) ? 'checked' : '' }}
                                       @if(in_array($extension, ['svg', 'zip', 'pdf', 'docx', 'tex']))
                                       x-on:change="updateRiskyType('{{ $extension }}', $event.target.checked)"
                                       @endif>
                                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-checked:bg-indigo-600 rounded-full transition-colors peer-focus:outline-none"></div>
                                <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
                            </div>
                            <span class="ml-3 text-sm text-gray-800 dark:text-gray-200">
                                {{ $fileExtensionNames[$extension] ?? strtoupper($extension) }}(.{{ $extension }})
                            </span>
                            @if(isset($fileExtensionWarnings[$extension]))
                                <i class="{{ $fileExtensionWarnings[$extension]['icon'] }} text-sm ml-1" title="{{ $fileExtensionWarnings[$extension]['title'] }}"></i>
                            @endif
                        </label>
                    </div>
                @endforeach
            </div>

            {{-- Warning when risky file types are enabled (real-time display) --}}
            <div x-show="hasRiskyTypes" x-transition class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-3"></i>
                    <div>
                        <h3 class="font-medium text-yellow-800 dark:text-yellow-200">{{ __('admin/media/settings.risky_types_warning_title') }}</h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">{{ __('admin/media/settings.risky_types_warning_description') }}</p>
                        <ul class="mt-2 space-y-1 text-sm text-yellow-700 dark:text-yellow-300">
                            <li x-show="riskyTypes.svg" x-transition class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 w-4"></i>
                                <strong>SVG:</strong>&nbsp;{{ __('admin/media/settings.risk.svg') }}
                            </li>
                            <li x-show="riskyTypes.zip" x-transition class="flex items-center">
                                <i class="fas fa-file-archive text-orange-500 mr-2 w-4"></i>
                                <strong>ZIP:</strong>&nbsp;{{ __('admin/media/settings.risk.zip') }}
                            </li>
                            <li x-show="riskyTypes.pdf" x-transition class="flex items-center">
                                <i class="fas fa-file-pdf text-red-400 mr-2 w-4"></i>
                                <strong>PDF:</strong>&nbsp;{{ __('admin/media/settings.risk.pdf') }}
                            </li>
                            <li x-show="riskyTypes.docx" x-transition class="flex items-center">
                                <i class="fas fa-file-word text-blue-400 mr-2 w-4"></i>
                                <strong>DOCX:</strong>&nbsp;{{ __('admin/media/settings.risk.docx') }}
                            </li>
                            <li x-show="riskyTypes.tex" x-transition class="flex items-center">
                                <i class="fas fa-file-alt text-gray-400 mr-2 w-4"></i>
                                <strong>TEX:</strong>&nbsp;{{ __('admin/media/settings.risk.tex') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Size limits per file type --}}
        <div class="mb-6">
            <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">{{ __('admin/media/settings.file_size_limits') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('admin/media/settings.file_size_limits_description') }}</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Images --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-image text-green-500 mr-2"></i>{{ __('admin/media/settings.category.image') }}
                        <span class="text-xs text-gray-500">(jpg, png, gif, webp, svg)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <x-form-text
                            type="number"
                            name="max_file_size_image"
                            :value="round(($securitySettings['max_file_size_image'] ?? 10240) / 1024)"
                            class="w-24"
                            :min="1"
                            :max="100"
                            :step="1"
                            :required="true"
                        />
                        <span class="text-gray-600 dark:text-gray-400">MB</span>
                    </div>
                </div>

                {{-- Videos --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-video text-purple-500 mr-2"></i>{{ __('admin/media/settings.category.video') }}
                        <span class="text-xs text-gray-500">(mp4)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <x-form-text
                            type="number"
                            name="max_file_size_video"
                            :value="round(($securitySettings['max_file_size_video'] ?? 307200) / 1024)"
                            class="w-24"
                            :min="1"
                            :max="1000"
                            :step="1"
                            :required="true"
                        />
                        <span class="text-gray-600 dark:text-gray-400">MB</span>
                    </div>
                </div>

                {{-- Documents --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-file-alt text-blue-500 mr-2"></i>{{ __('admin/media/settings.category.document') }}
                        <span class="text-xs text-gray-500">(pdf, docx, txt)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <x-form-text
                            type="number"
                            name="max_file_size_document"
                            :value="round(($securitySettings['max_file_size_document'] ?? 30720) / 1024)"
                            class="w-24"
                            :min="1"
                            :max="100"
                            :step="1"
                            :required="true"
                        />
                        <span class="text-gray-600 dark:text-gray-400">MB</span>
                    </div>
                </div>

                {{-- Archives --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-file-archive text-orange-500 mr-2"></i>{{ __('admin/media/settings.category.archive') }}
                        <span class="text-xs text-gray-500">(zip)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <x-form-text
                            type="number"
                            name="max_file_size_archive"
                            :value="round(($securitySettings['max_file_size_archive'] ?? 102400) / 1024)"
                            class="w-24"
                            :min="1"
                            :max="500"
                            :step="1"
                            :required="true"
                        />
                        <span class="text-gray-600 dark:text-gray-400">MB</span>
                    </div>
                </div>
            </div>

            {{-- For legacy compatibility (hidden) --}}
            <input type="hidden" name="max_file_size" value="{{ round($maxFileSize / 1024, 1) }}">
        </div>

        {{-- Security settings --}}
        <div class="mb-6">
            <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">
                <i class="fas fa-shield-alt text-blue-500 mr-2"></i>{{ __('admin/media/settings.security') }}
            </h2>

            <div class="space-y-4">
                {{-- MIME content verification --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <div class="flex items-center justify-start">
                        <x-form-toggle
                            name="mime_validation_enabled"
                            :checked="($securitySettings['mime_validation_enabled'] ?? true)"
                        />
                        <div class="ml-3">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('admin/media/settings.mime_validation') }}</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/media/settings.mime_validation_description') }}</p>
                        </div>

                    </div>
                </div>

                {{-- SVG sanitization --}}
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <div class="flex items-center justify-start">
                        <x-form-toggle
                            name="svg_sanitization_enabled"
                            :checked="($securitySettings['svg_sanitization_enabled'] ?? true)"
                        />
                        <div class="ml-3">
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>{{ __('admin/media/settings.svg_sanitization') }}
                            </span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/media/settings.svg_sanitization_description') }}</p>
                        </div>

                    </div>
                </div>

                {{-- ZIP security --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-start">
                        <x-form-toggle
                            name="zip_security_enabled"
                            :checked="($securitySettings['zip_security_enabled'] ?? true)"
                            :xModel="'showZipSettings'"
                        />
                        <div class="ml-3">
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                <i class="fas fa-file-archive text-blue-500 mr-2"></i>{{ __('admin/media/settings.zip_security') }}
                            </span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/media/settings.zip_security_description') }}</p>
                        </div>

                    </div>

                    {{-- ZIP advanced settings --}}
                    <div x-show="showZipSettings" x-transition class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('admin/media/settings.zip_max_compression_ratio') }}
                                </label>
                                <div class="flex items-center space-x-2">
                                    <x-form-text
                                        type="number"
                                        name="zip_max_compression_ratio"
                                        :value="$securitySettings['zip_max_compression_ratio'] ?? 100"
                                        class="w-24"
                                        :min="10"
                                        :max="1000"
                                        :step="10"
                                        :required="true"
                                    />
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('admin/media/settings.times') }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ __('admin/media/settings.zip_compression_ratio_help') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('admin/media/settings.zip_max_file_count') }}
                                </label>
                                <div class="flex items-center space-x-2">
                                    <x-form-text
                                        type="number"
                                        name="zip_max_file_count"
                                        :value="$securitySettings['zip_max_file_count'] ?? 1000"
                                        class="w-24"
                                        :min="10"
                                        :max="10000"
                                        :step="10"
                                        :required="true"
                                    />
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('admin/media/settings.files') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>
@endsection

@section('save')
    <!-- 保存ボタンとモーダル -->
    <x-admin.save-button
        id="confirmationModal"
        :label="__('common.save')"
        @click="openModal('confirmationModal')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm-label="__('common.save')"
        :cancel-label="__('common.cancel')"
        :form="'media-settings-form'"
    />
@endsection