{{--
This file is part of MySoftware.

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
    'file',
    'mediaPath' => ''
])

<div class="media-card">
    <div class="media-card__preview">
        <a href="{{ route('admin.media.preview', $file->id) }}" target="_blank" aria-label="{{ __('admin.media.index.preview') }} {{ $file->name }}">
            @if(in_array($file->type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
                <img src="{{ asset('storage/' . $mediaPath . '/' . $file->path) }}" 
                     alt="{{ $file->name }}" 
                     class="media-card__image">
            @else
                <div class="media-card__placeholder">
                    <i class="fas fa-file-alt" aria-hidden="true"></i>
                </div>
            @endif
        </a>
    </div>

    <div class="media-card__content">
        <h3 class="media-card__title">
            <a href="{{ route('admin.media.preview', $file->id) }}" 
               target="_blank" 
               class="text-link">{{ $file->name }}</a>
        </h3>
        
        <div class="media-card__meta">
            <span class="media-card__type">{{ $file->type }}</span>
            @if($file->member)
                <span class="media-card__uploader">{{ $file->member->name }}</span>
            @endif
            <time class="media-card__date" datetime="{{ $file->created_at->format('Y-m-d') }}">
                {{ $file->created_at->format('Y/m/d') }}
            </time>
        </div>

        <div class="media-card__actions">
            <a href="{{ route('admin.media.download', $file->id) }}" 
               class="action-btn action-btn--download"
               title="{{ __('admin.media.index.download') }}" 
               aria-label="{{ __('admin.media.index.download') }} {{ $file->name }}">
                <i class="fas fa-download" aria-hidden="true"></i>
            </a>

            <a href="{{ route('admin.media.preview', $file->id) }}" 
               target="_blank" 
               class="action-btn action-btn--preview"
               title="{{ __('admin.media.index.preview') }}" 
               aria-label="{{ __('admin.media.index.preview') }} {{ $file->name }}">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </a>

            <button type="button" 
                    class="action-btn action-btn--delete"
                    title="{{ __('admin.media.index.delete') }}" 
                    aria-label="{{ __('admin.media.index.delete') }} {{ $file->name }}"
                    onclick="openDeleteModal({{ $file->id }}, '{{ addslashes($file->name) }}')">
                <i class="fas fa-trash-alt" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

