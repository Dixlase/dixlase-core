<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Http\Controllers\Admin\Media;

use App\Actions\Media\DeleteMediaAction;
use App\Actions\Media\UpdateMediaMetadataAction;
use App\Actions\Media\UpdateMediaSettingsAction;
use App\Actions\Media\UploadMediaAction;
use App\Actors\MemberActor;
use App\Contracts\Repositories\MediaRepositoryInterface;
use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Media\AdminMediaSettingsUpdateRequest;
use App\Http\Requests\Admin\Media\AdminMediaStoreRequest;
use App\Http\Requests\Admin\Media\AdminMediaUpdateRequest;
use App\Models\Media;
use App\Services\Media\MediaSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminMediaController extends AdminLoggedInController
{
    /**
     * Media settings repository
     */
    protected MediaSettingRepositoryInterface $mediaSettingRepository;

    /**
     * Media repository
     */
    protected MediaRepositoryInterface $mediaRepository;

    /**
     * Media security service
     */
    protected MediaSecurityService $mediaSecurityService;

    /**
     * Constructor
     */
    public function __construct(
        MediaSettingRepositoryInterface $mediaSettingRepository,
        MediaRepositoryInterface $mediaRepository,
        MediaSecurityService $mediaSecurityService
    ) {
        parent::__construct();
        $this->mediaSettingRepository = $mediaSettingRepository;
        $this->mediaRepository = $mediaRepository;
        $this->mediaSecurityService = $mediaSecurityService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get items per page (default: 25)
        $perPage = $request->get('per_page', 25);

        // Allow only valid per-page values
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }

        // Get sort settings
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');

        // Allow only valid sort fields
        $allowedSorts = ['name', 'type', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        // Allow only valid sort order
        if (! in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // Get search parameters
        $search = $request->get('search');
        $fileType = $request->get('file_type');
        $uploadedBy = $request->get('uploaded_by');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Build filter array
        $filters = [];
        if ($search) {
            $filters['search'] = $search;
        }
        if ($fileType) {
            $filters['type'] = $fileType;
        }
        if ($uploadedBy) {
            $filters['uploaded_by'] = $uploadedBy;
        }
        if ($dateFrom) {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $filters['date_to'] = $dateTo;
        }

        // Execute pagination using repository
        $media = $this->mediaRepository->paginate($perPage, $filters, $sort, $order)
            ->withQueryString(); // Preserve URL parameters

        $this->viewParams['media'] = $media;
        $this->viewParams['currentSort'] = $sort;
        $this->viewParams['currentOrder'] = $order;
        $this->viewParams['search'] = $search;
        $this->viewParams['fileType'] = $fileType;
        $this->viewParams['uploadedBy'] = $uploadedBy;
        $this->viewParams['dateFrom'] = $dateFrom;
        $this->viewParams['dateTo'] = $dateTo;

        return view('admin.media.index', $this->viewParams);
    }

    public function upload()
    {
        // Load allowed file types
        $allowedFileTypes = $this->mediaSettingRepository->get('allowed_file_types', []);
        $this->viewParams['allowedFileTypes'] = $allowedFileTypes;

        // Display current media settings summary
        $this->viewParams['isSimpleMode'] = AdminModeHelper::isSimpleMode();

        $securitySettings = $this->mediaSecurityService->getSecuritySettings();
        $this->viewParams['fileSizeLimits'] = [
            'image' => round(($securitySettings['max_file_size_image'] ?? 10240) / 1024),
            'video' => round(($securitySettings['max_file_size_video'] ?? 307200) / 1024),
            'document' => round(($securitySettings['max_file_size_document'] ?? 30720) / 1024),
            'archive' => round(($securitySettings['max_file_size_archive'] ?? 102400) / 1024),
        ];
        $this->viewParams['securityStatus'] = [
            'mime_validation' => (bool) ($securitySettings['mime_validation_enabled'] ?? true),
            'svg_sanitization' => (bool) ($securitySettings['svg_sanitization_enabled'] ?? true),
            'zip_security' => (bool) ($securitySettings['zip_security_enabled'] ?? true),
        ];

        // File type display name
        $fileExtensionNames = config('admin.files.fileExtensionNames', []);
        $this->viewParams['fileExtensionNames'] = $fileExtensionNames;

        return view('admin.media.upload', $this->viewParams);
    }

    /**
     * Upload and save file
     */
    public function store(AdminMediaStoreRequest $request)
    {
        $files = $request->file('files', []);
        $singleFile = $request->file('file');

        // Convert to array if single file (backward compatibility)
        if ($singleFile && empty($files)) {
            $files = [$singleFile];
        }

        if (empty($files)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => __('admin/media/upload.error.file_not_found')], 422);
            }

            return redirect()->back()->withErrors(['file' => __('admin/media/upload.error.file_not_found')]);
        }

        $actor = new MemberActor(AdminHelper::getMember());
        $uploadAction = app(UploadMediaAction::class);
        $results = [];
        $allWarnings = [];

        foreach ($files as $file) {
            $result = $uploadAction->execute($actor, [
                'file' => $file,
                'uploaded_by' => $this->member->id,
            ]);

            if ($result->success) {
                $results[] = [
                    'success' => true,
                    'name' => $file->getClientOriginalName(),
                    'id' => $result->model->id,
                ];
                $allWarnings = array_merge($allWarnings, $result->metadata['warnings'] ?? []);
            } else {
                $results[] = [
                    'success' => false,
                    'name' => $file->getClientOriginalName(),
                    'error' => $result->message,
                ];
            }
        }

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            $successCount = count(array_filter($results, fn ($r) => $r['success']));
            $failCount = count(array_filter($results, fn ($r) => ! $r['success']));

            return response()->json([
                'success' => $successCount > 0,
                'results' => $results,
                'message' => __('admin/media/index.success.uploaded_count', ['count' => $successCount]),
                'warnings' => $allWarnings,
                'successCount' => $successCount,
                'failCount' => $failCount,
            ]);
        }

        // For normal form submission
        if (! empty($allWarnings)) {
            session()->flash('warnings', $allWarnings);
        }

        return redirect()->route('admin.media.index')->with('success', __('admin/media/index.success.uploaded'));
    }

    public function delete(Media $media)
    {
        $actor = new MemberActor(AdminHelper::getMember());
        (new DeleteMediaAction($media))->execute($actor, []);

        return redirect()->route('admin.media.index')->with('success', __('admin/media/index.success.deleted'));
    }

    public function download(Media $media)
    {
        $disk = config('admin.files.storageDisk', 'public');
        $mediaPath = config('admin.files.mediaPath', 'media');

        // Consider cases where path field contains only filename or full path
        if (strpos($media->path, $mediaPath) === 0) {
            // When full path is stored
            $filePath = $media->path;
        } else {
            // When only filename is stored
            $filePath = $mediaPath.'/'.$media->path;
        }

        if (! Storage::disk($disk)->exists($filePath)) {
            abort(404, __('admin/media/index.error.file_not_exists'));
        }

        // Generate secure download response
        $fullPath = Storage::disk($disk)->path($filePath);

        return $this->mediaSecurityService->createSecureDownloadResponse(
            $fullPath,
            $media->name,
            $media->type
        );
    }

    public function preview(Media $media)
    {
        // Preload member information
        $media->load('member');

        // Consider cases where path field contains only filename or full path
        $mediaPath = config('admin.files.mediaPath', 'media');
        if (strpos($media->path, $mediaPath) === 0) {
            // When full path is stored
            $filePath = storage_path('app/'.config('admin.files.storageDisk').'/'.$media->path);
        } else {
            // When only filename is stored
            $filePath = storage_path('app/'.config('admin.files.storageDisk').'/'.$mediaPath.'/'.$media->path);
        }

        if (! file_exists($filePath)) {
            abort(404, __('http/controllers/admin/media/admin_media_controller.file_does_not_exist'));
        }

        $this->viewParams['media'] = $media;
        $this->viewParams['filePath'] = $filePath;

        return view('admin.media.preview', $this->viewParams);
    }

    /**
     * Update media information
     */
    public function updateMedia(AdminMediaUpdateRequest $request, Media $media)
    {
        $actor = new MemberActor(AdminHelper::getMember());
        (new UpdateMediaMetadataAction($media, $this->mediaRepository))->execute($actor, $request->validated());

        return redirect()->route('admin.media.preview', $media->id)
            ->with('success', __('http/controllers/admin/media/admin_media_controller.media_info_updated'));
    }

    public function settings()
    {
        // Media settings are hidden in easy mode as they are automatically configured
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.media.index');
        }

        $allowedFileTypes = $this->mediaSettingRepository->get('allowed_file_types', []);
        $maxFileSize = $this->mediaSettingRepository->get('max_file_size', '2048');

        $fileExtensions = config('admin.files.fileExtensions');
        $fileExtensionNames = config('admin.files.fileExtensionNames');

        // Get security settings
        $securitySettings = $this->mediaSecurityService->getSecuritySettings();

        $this->viewParams['allowedFileTypes'] = $allowedFileTypes;
        $this->viewParams['maxFileSize'] = $maxFileSize;
        $this->viewParams['fileExtensions'] = $fileExtensions;
        $this->viewParams['fileExtensionNames'] = $fileExtensionNames;
        $this->viewParams['securitySettings'] = $securitySettings;
        $this->viewParams['fileExtensionWarnings'] = [
            'svg' => ['icon' => 'fas fa-exclamation-triangle text-yellow-500', 'title' => __('admin/media/settings.svg_warning')],
            'zip' => ['icon' => 'fas fa-file-archive text-orange-500', 'title' => __('admin/media/settings.zip_warning')],
            'pdf' => ['icon' => 'fas fa-file-pdf text-red-400', 'title' => __('admin/media/settings.pdf_warning')],
            'docx' => ['icon' => 'fas fa-file-word text-blue-400', 'title' => __('admin/media/settings.docx_warning')],
            'tex' => ['icon' => 'fas fa-file-alt text-gray-400', 'title' => __('admin/media/settings.tex_warning')],
        ];

        return view('admin.media.settings', $this->viewParams);
    }

    /**
     * API: Get media list (for modal)
     */
    public function api(Request $request)
    {
        $perPage = $request->get('per_page', 12);
        $search = $request->get('search');
        $type = $request->get('type');

        // Build filter array
        $filters = [];
        if ($search) {
            $filters['search'] = $search;
        }
        if ($type) {
            $filters['type'] = $type;
        }

        // Execute pagination using repository
        $media = $this->mediaRepository->paginate($perPage, $filters, 'created_at', 'desc');

        // Add URL
        $mediaPath = config('admin.files.mediaPath', 'media');
        $media->getCollection()->transform(function ($item) use ($mediaPath) {
            // Consider cases where path field contains only filename or full path
            if (strpos($item->path, $mediaPath.'/') === 0) {
                // When full path is stored (media/filename.jpg)
                $item->url = asset('storage/'.$item->path);
            } else {
                // When only filename is stored (filename.jpg)
                $item->url = asset('storage/'.$mediaPath.'/'.$item->path);
            }

            return $item;
        });

        return response()->json([
            'success' => true,
            'media' => $media,
        ]);
    }

    /**
     * Update media settings
     */
    public function update(AdminMediaSettingsUpdateRequest $request)
    {
        // Prohibit changing media settings in easy mode
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.media.index');
        }

        $actor = new MemberActor(AdminHelper::getMember());
        $action = new UpdateMediaSettingsAction($this->mediaSettingRepository);
        $action->execute($actor, $request->validated());

        return redirect()->back()->with('success', __('admin/media/settings.success.settings_updated'));
    }
}
