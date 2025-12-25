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

        {{-- 許可するファイルタイプ --}}
        <div class="mb-6">
            <h2>{{ __('admin/media.settings.allowed_file_types') }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($fileExtensions as $extension)
                    <label class="flex items-center space-x-2 cursor-pointer bg-gray-100 dark:bg-gray-700 p-3 rounded-lg shadow-sm hover:bg-gray-200 dark:hover:bg-gray-600">
                        <input type="checkbox" name="allowed_file_types[]" value="{{ $extension }}" 
                               class="form-checkbox h-5 w-5 text-blue-600 dark:text-blue-400"
                               {{ in_array($extension, $allowedFileTypes) ? 'checked' : '' }}
                               @if(in_array($extension, ['svg', 'zip', 'pdf', 'docx', 'tex']))
                               x-on:change="updateRiskyType('{{ $extension }}', $event.target.checked)"
                               @endif>
                        <span class="text-sm text-gray-800 dark:text-gray-200">
                            {{ $fileExtensionNames[$extension] ?? strtoupper($extension) }}(.{{ $extension }})
                        </span>
                        @if($extension === 'svg')
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-sm ml-1" title="{{ __('admin/media.settings.svg_warning') }}"></i>
                        @elseif($extension === 'zip')
                            <i class="fas fa-file-archive text-orange-500 text-sm ml-1" title="{{ __('admin/media.settings.zip_warning') }}"></i>
                        @elseif($extension === 'pdf')
                            <i class="fas fa-file-pdf text-red-400 text-sm ml-1" title="{{ __('admin/media.settings.pdf_warning') }}"></i>
                        @elseif($extension === 'docx')
                            <i class="fas fa-file-word text-blue-400 text-sm ml-1" title="{{ __('admin/media.settings.docx_warning') }}"></i>
                        @elseif($extension === 'tex')
                            <i class="fas fa-file-alt text-gray-400 text-sm ml-1" title="{{ __('admin/media.settings.tex_warning') }}"></i>
                        @endif
                    </label>
                @endforeach
            </div>

            {{-- リスクのあるファイルタイプが有効な場合の警告（リアルタイム表示） --}}
            <div x-show="hasRiskyTypes" x-transition class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-3"></i>
                    <div>
                        <h3 class="font-medium text-yellow-800 dark:text-yellow-200">{{ __('admin/media.settings.risky_types_warning_title') }}</h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">{{ __('admin/media.settings.risky_types_warning_description') }}</p>
                        <ul class="mt-2 space-y-1 text-sm text-yellow-700 dark:text-yellow-300">
                            <li x-show="riskyTypes.svg" x-transition class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 w-4"></i>
                                <strong>SVG:</strong>&nbsp;{{ __('admin/media.settings.risk.svg') }}
                            </li>
                            <li x-show="riskyTypes.zip" x-transition class="flex items-center">
                                <i class="fas fa-file-archive text-orange-500 mr-2 w-4"></i>
                                <strong>ZIP:</strong>&nbsp;{{ __('admin/media.settings.risk.zip') }}
                            </li>
                            <li x-show="riskyTypes.pdf" x-transition class="flex items-center">
                                <i class="fas fa-file-pdf text-red-400 mr-2 w-4"></i>
                                <strong>PDF:</strong>&nbsp;{{ __('admin/media.settings.risk.pdf') }}
                            </li>
                            <li x-show="riskyTypes.docx" x-transition class="flex items-center">
                                <i class="fas fa-file-word text-blue-400 mr-2 w-4"></i>
                                <strong>DOCX:</strong>&nbsp;{{ __('admin/media.settings.risk.docx') }}
                            </li>
                            <li x-show="riskyTypes.tex" x-transition class="flex items-center">
                                <i class="fas fa-file-alt text-gray-400 mr-2 w-4"></i>
                                <strong>TEX:</strong>&nbsp;{{ __('admin/media.settings.risk.tex') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ファイルタイプ別サイズ上限 --}}
        <div class="mb-6">
            <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">{{ __('admin/media.settings.file_size_limits') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('admin/media.settings.file_size_limits_description') }}</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- 画像 --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-image text-green-500 mr-2"></i>{{ __('admin/media.settings.category.image') }}
                        <span class="text-xs text-gray-500">(jpg, png, gif, webp, svg)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="max_file_size_image" value="{{ round(($securitySettings['max_file_size_image'] ?? 10240) / 1024) }}" 
                               class="form-input w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                               min="1" max="100" step="1" required>
                        <span class="text-gray-600 dark:text-gray-400">MB</span>
                    </div>
                </div>

                {{-- 動画 --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-video text-purple-500 mr-2"></i>{{ __('admin/media.settings.category.video') }}
                        <span class="text-xs text-gray-500">(mp4)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="max_file_size_video" value="{{ round(($securitySettings['max_file_size_video'] ?? 307200) / 1024) }}" 
                               class="form-input w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                               min="1" max="1000" step="1" required>
                        <span class="text-gray-600 dark:text-gray-400">MB</span>
                    </div>
                </div>

                {{-- ドキュメント --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-file-alt text-blue-500 mr-2"></i>{{ __('admin/media.settings.category.document') }}
                        <span class="text-xs text-gray-500">(pdf, docx, txt)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="max_file_size_document" value="{{ round(($securitySettings['max_file_size_document'] ?? 30720) / 1024) }}" 
                               class="form-input w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                               min="1" max="100" step="1" required>
                        <span class="text-gray-600 dark:text-gray-400">MB</span>
                    </div>
                </div>

                {{-- アーカイブ --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-file-archive text-orange-500 mr-2"></i>{{ __('admin/media.settings.category.archive') }}
                        <span class="text-xs text-gray-500">(zip)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="max_file_size_archive" value="{{ round(($securitySettings['max_file_size_archive'] ?? 102400) / 1024) }}" 
                               class="form-input w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                               min="1" max="500" step="1" required>
                        <span class="text-gray-600 dark:text-gray-400">MB</span>
                    </div>
                </div>
            </div>

            {{-- レガシー互換用（非表示） --}}
            <input type="hidden" name="max_file_size" value="{{ round($maxFileSize / 1024, 1) }}">
        </div>

        {{-- セキュリティ設定 --}}
        <div class="mb-6">
            <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">
                <i class="fas fa-shield-alt text-blue-500 mr-2"></i>{{ __('admin/media.settings.security') }}
            </h2>

            <div class="space-y-4">
                {{-- MIME実体検証 --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('admin/media.settings.mime_validation') }}</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/media.settings.mime_validation_description') }}</p>
                        </div>
                        <input type="checkbox" name="mime_validation_enabled" value="1" 
                               class="form-checkbox h-5 w-5 text-blue-600"
                               {{ ($securitySettings['mime_validation_enabled'] ?? true) ? 'checked' : '' }}>
                    </label>
                </div>

                {{-- SVGサニタイズ --}}
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>{{ __('admin/media.settings.svg_sanitization') }}
                            </span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/media.settings.svg_sanitization_description') }}</p>
                        </div>
                        <input type="checkbox" name="svg_sanitization_enabled" value="1" 
                               class="form-checkbox h-5 w-5 text-yellow-600"
                               {{ ($securitySettings['svg_sanitization_enabled'] ?? true) ? 'checked' : '' }}>
                    </label>
                </div>

                {{-- ZIPセキュリティ --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                <i class="fas fa-file-archive text-blue-500 mr-2"></i>{{ __('admin/media.settings.zip_security') }}
                            </span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/media.settings.zip_security_description') }}</p>
                        </div>
                        <input type="checkbox" name="zip_security_enabled" value="1" 
                               class="form-checkbox h-5 w-5 text-blue-600"
                               x-on:change="showZipSettings = $event.target.checked"
                               {{ ($securitySettings['zip_security_enabled'] ?? true) ? 'checked' : '' }}>
                    </label>

                    {{-- ZIP詳細設定 --}}
                    <div x-show="showZipSettings" x-transition class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('admin/media.settings.zip_max_compression_ratio') }}
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="number" name="zip_max_compression_ratio" 
                                           value="{{ $securitySettings['zip_max_compression_ratio'] ?? 100 }}" 
                                           class="form-input w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                           min="10" max="1000" step="10" required>
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('admin/media.settings.times') }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ __('admin/media.settings.zip_compression_ratio_help') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('admin/media.settings.zip_max_file_count') }}
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="number" name="zip_max_file_count" 
                                           value="{{ $securitySettings['zip_max_file_count'] ?? 1000 }}" 
                                           class="form-input w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                           min="10" max="10000" step="10" required>
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('admin/media.settings.files') }}</span>
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
    <x-save
        id="confirmationModal"
        :label="__('common.save')"
        onclick="openModal('confirmationModal')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm-label="__('common.save')"
        :cancel-label="__('common.cancel')"
        :form="'media-settings-form'"
    />
@endsection