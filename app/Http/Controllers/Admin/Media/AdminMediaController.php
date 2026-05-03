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
     * メディア設定リポジトリ
     */
    protected MediaSettingRepositoryInterface $mediaSettingRepository;

    /**
     * メディアリポジトリ
     */
    protected MediaRepositoryInterface $mediaRepository;

    /**
     * メディアセキュリティサービス
     */
    protected MediaSecurityService $mediaSecurityService;

    /**
     * コンストラクタ
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
        // 1ページあたりの表示件数を取得（デフォルト: 25）
        $perPage = $request->get('per_page', 25);

        // 有効な表示件数のみ許可
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }

        // ソート設定を取得
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');

        // 有効なソートフィールドのみ許可
        $allowedSorts = ['name', 'type', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        // 有効なソート順序のみ許可
        if (! in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // 検索パラメータを取得
        $search = $request->get('search');
        $fileType = $request->get('file_type');
        $uploadedBy = $request->get('uploaded_by');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // フィルター配列を構築
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

        // リポジトリを使用してページネーション実行
        $media = $this->mediaRepository->paginate($perPage, $filters, $sort, $order)
            ->withQueryString(); // URLパラメータを保持

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
        // 許可されたファイルタイプを読み込み
        $allowedFileTypes = $this->mediaSettingRepository->get('allowed_file_types', []);
        $this->viewParams['allowedFileTypes'] = $allowedFileTypes;

        // 現在のメディア設定サマリーを表示
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

        // ファイルタイプ表示名
        $fileExtensionNames = config('admin.files.fileExtensionNames', []);
        $this->viewParams['fileExtensionNames'] = $fileExtensionNames;

        return view('admin.media.upload', $this->viewParams);
    }

    /**
     * ファイルをアップロードして保存する
     */
    public function store(AdminMediaStoreRequest $request)
    {
        $files = $request->file('files', []);
        $singleFile = $request->file('file');

        // 単一ファイルの場合は配列に変換（後方互換性）
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

        // AJAX リクエストの場合はJSONを返す
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

        // 通常のフォーム送信の場合
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

        // pathフィールドがファイル名のみの場合とフルパスの場合を考慮
        if (strpos($media->path, $mediaPath) === 0) {
            // フルパスが保存されている場合
            $filePath = $media->path;
        } else {
            // ファイル名のみが保存されている場合
            $filePath = $mediaPath.'/'.$media->path;
        }

        if (! Storage::disk($disk)->exists($filePath)) {
            abort(404, __('admin/media/index.error.file_not_exists'));
        }

        // セキュアなダウンロードレスポンスを生成
        $fullPath = Storage::disk($disk)->path($filePath);

        return $this->mediaSecurityService->createSecureDownloadResponse(
            $fullPath,
            $media->name,
            $media->type
        );
    }

    public function preview(Media $media)
    {
        // メンバー情報を事前に読み込み
        $media->load('member');

        // pathフィールドがファイル名のみの場合とフルパスの場合を考慮
        $mediaPath = config('admin.files.mediaPath', 'media');
        if (strpos($media->path, $mediaPath) === 0) {
            // フルパスが保存されている場合
            $filePath = storage_path('app/'.config('admin.files.storageDisk').'/'.$media->path);
        } else {
            // ファイル名のみが保存されている場合
            $filePath = storage_path('app/'.config('admin.files.storageDisk').'/'.$mediaPath.'/'.$media->path);
        }

        if (! file_exists($filePath)) {
            abort(404, 'ファイルが存在しません');
        }

        $this->viewParams['media'] = $media;
        $this->viewParams['filePath'] = $filePath;

        return view('admin.media.preview', $this->viewParams);
    }

    /**
     * メディア情報を更新
     */
    public function updateMedia(AdminMediaUpdateRequest $request, Media $media)
    {
        $actor = new MemberActor(AdminHelper::getMember());
        (new UpdateMediaMetadataAction($media, $this->mediaRepository))->execute($actor, $request->validated());

        return redirect()->route('admin.media.preview', $media->id)
            ->with('success', 'メディア情報が更新されました。');
    }

    public function settings()
    {
        // かんたんモードではメディア設定は自動設定のため非表示
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.media.index');
        }

        $allowedFileTypes = $this->mediaSettingRepository->get('allowed_file_types', []);
        $maxFileSize = $this->mediaSettingRepository->get('max_file_size', '2048');

        $fileExtensions = config('admin.files.fileExtensions');
        $fileExtensionNames = config('admin.files.fileExtensionNames');

        // セキュリティ設定を取得
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
     * API: メディア一覧を取得（モーダル用）
     */
    public function api(Request $request)
    {
        $perPage = $request->get('per_page', 12);
        $search = $request->get('search');
        $type = $request->get('type');

        // フィルター配列を構築
        $filters = [];
        if ($search) {
            $filters['search'] = $search;
        }
        if ($type) {
            $filters['type'] = $type;
        }

        // リポジトリを使用してページネーション実行
        $media = $this->mediaRepository->paginate($perPage, $filters, 'created_at', 'desc');

        // URLを追加
        $mediaPath = config('admin.files.mediaPath', 'media');
        $media->getCollection()->transform(function ($item) use ($mediaPath) {
            // pathフィールドがファイル名のみの場合とフルパスの場合を考慮
            if (strpos($item->path, $mediaPath.'/') === 0) {
                // フルパスが保存されている場合（media/filename.jpg）
                $item->url = asset('storage/'.$item->path);
            } else {
                // ファイル名のみが保存されている場合（filename.jpg）
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
     * メディア設定を更新
     */
    public function update(AdminMediaSettingsUpdateRequest $request)
    {
        // かんたんモードではメディア設定の変更を禁止
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.media.index');
        }

        $actor = new MemberActor(AdminHelper::getMember());
        $action = new UpdateMediaSettingsAction($this->mediaSettingRepository);
        $action->execute($actor, $request->validated());

        return redirect()->back()->with('success', __('admin/media/settings.success.settings_updated'));
    }
}
