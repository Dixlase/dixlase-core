<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Services\Media;

use App\Actions\Media\UploadMediaAction;
use App\Actors\MemberActor;
use App\Models\Media;
use App\Models\Member;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Register local files in the media library (#902)
 *
 * The command-line counterpart of the admin upload. Each file is checked
 * against the same allowed types and size as the upload form
 * ({@see MediaUploadLimits}) and then stored through
 * {@see UploadMediaAction}, so it gets the same security checks, SVG
 * sanitising and audit entry, recorded under the member given.
 *
 * A file whose content is identical to an existing media item is not stored
 * a second time; the existing item is returned instead, so importing the
 * same article twice does not fill the library with copies.
 *
 * @api
 */
class MediaImporter
{
    public function __construct(
        private readonly MediaUploadLimits $limits,
    ) {}

    public function import(string $path, Member $uploader): MediaImportResult
    {
        $real = realpath($path);
        if ($real === false || ! is_file($real) || ! is_readable($real)) {
            return MediaImportResult::failed($path, __('admin/media/import.not_a_file', ['path' => $path]));
        }

        $file = new UploadedFile($real, basename($real), null, null, true);

        $error = $this->validationError($file);
        if ($error !== null) {
            return MediaImportResult::failed($path, $error);
        }

        $existing = $this->findIdentical($real);
        if ($existing !== null) {
            return MediaImportResult::reused($path, $existing);
        }

        try {
            $result = app(UploadMediaAction::class)->execute(new MemberActor($uploader), [
                'file' => $file,
                'uploaded_by' => $uploader->id,
            ]);
        } catch (AuthorizationException) {
            return MediaImportResult::failed($path, __('admin/media/import.not_allowed', ['member' => $uploader->email]));
        } catch (Throwable $e) {
            report($e);

            return MediaImportResult::failed($path, __('admin/media/index.error.save_failed'));
        }

        if (! $result->success || ! $result->model instanceof Media) {
            return MediaImportResult::failed($path, (string) ($result->message ?: __('admin/media/index.error.save_failed')));
        }

        /** @var list<string> $warnings */
        $warnings = array_values($result->metadata['warnings'] ?? []);

        return MediaImportResult::imported($path, $result->model, $warnings);
    }

    /**
     * Root-relative URL path of a media item, as the editor inserts it
     */
    public static function pathOf(Media $media): string
    {
        return (string) parse_url(self::urlOf($media), PHP_URL_PATH);
    }

    /**
     * Absolute URL of a media item
     */
    public static function urlOf(Media $media): string
    {
        return asset('storage/'.self::storedPath($media));
    }

    /**
     * The same type and size rules as the admin upload form, with its messages
     */
    private function validationError(UploadedFile $file): ?string
    {
        $types = $this->limits->allowedFileTypes();
        $maxKb = $this->limits->maxFileSizeKb();
        $messages = 'http/requests/admin/media/admin_media_store_request.';

        $validator = Validator::make(['file' => $file], [
            'file' => 'required|file|mimes:'.implode(',', $types).'|max:'.$maxKb,
        ], [
            'file.file' => __($messages.'please_upload_valid_file'),
            'file.mimes' => __($messages.'allowed_file_types_are').implode(', ', $types).__($messages.'period'),
            'file.max' => __($messages.'file_size_exceeds_limit', ['maxFileSizeMB' => round($maxKb / 1024)]),
        ]);

        return $validator->fails() ? (string) $validator->errors()->first('file') : null;
    }

    /**
     * An existing media item with exactly this content, if any
     */
    private function findIdentical(string $real): ?Media
    {
        $size = filesize($real);
        $hash = hash_file('sha256', $real);
        if ($size === false || $hash === false) {
            return null;
        }

        $disk = Storage::disk(config('admin.files.storageDisk'));

        foreach (Media::where('file_size', $size)->orderBy('id')->cursor() as $media) {
            $stored = self::storedPath($media);
            if (! $disk->exists($stored)) {
                continue;
            }

            if (hash_file('sha256', $disk->path($stored)) === $hash) {
                return $media;
            }
        }

        return null;
    }

    /**
     * Path of the stored file on the media disk; the column holds either the
     * file name alone or the path including the media directory
     */
    private static function storedPath(Media $media): string
    {
        $mediaPath = (string) config('admin.files.mediaPath', 'media');

        return str_starts_with((string) $media->path, $mediaPath.'/')
            ? (string) $media->path
            : $mediaPath.'/'.$media->path;
    }
}
