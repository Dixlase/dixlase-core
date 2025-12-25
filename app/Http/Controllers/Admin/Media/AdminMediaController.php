<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use App\Models\MediaSetting;
use App\Http\Requests\Admin\Media\AdminMediaStoreRequest;
use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Contracts\Repositories\MediaRepositoryInterface;
use App\Services\Media\MediaSecurityService;

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
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }
        
        // ソート設定を取得
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        
        // 有効なソートフィールドのみ許可
        $allowedSorts = ['name', 'type', 'created_at', 'updated_at'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        
        // 有効なソート順序のみ許可
        if (!in_array($order, ['asc', 'desc'])) {
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
        //許可されたファイルタイプを読み込み
        $allowedFileTypes = $this->mediaSettingRepository->get('allowed_file_types', []);
        $this->viewParams['allowedFileTypes'] = $allowedFileTypes;

        return view('admin.media.upload', $this->viewParams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminMediaStoreRequest $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return redirect()->back()->withErrors(['file' => __('admin/media.error.file_not_found')]);
        }

        // セキュリティチェック
        $securityResult = $this->mediaSecurityService->validateUpload($file);
        if (!$securityResult->isValid()) {
            return redirect()->back()->withErrors(['file' => $securityResult->getFirstError()]);
        }

        // メンバーIDを取得
        $memberId = $this->member->id;

        try {
            $path = $file->store(config('admin.mediaPath'), config('admin.storageDisk'));
            $fileName = basename($path);
            
            // SVGファイルの場合、サニタイズを実行
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension === 'svg') {
                $fullPath = Storage::disk(config('admin.storageDisk'))->path($path);
                $this->mediaSecurityService->sanitizeSvgIfNeeded($fullPath);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file' => __('admin/media.error.save_failed')]);
        }

        $this->mediaRepository->create([
            'name' => $file->getClientOriginalName(),
            'path' => $fileName,
            'type' => $file->getMimeType(),
            'uploaded_by' => $memberId,
        ]);

        // 警告がある場合はセッションに保存
        if ($securityResult->hasWarnings()) {
            $warnings = array_map(fn($w) => $w['message'], $securityResult->getWarnings());
            session()->flash('warnings', $warnings);
        }

        return redirect()->route('admin.media.index')->with('success', __('admin/media.success.uploaded'));
    }


    public function delete(Media $media)
    {
        $disk = config('admin.storageDisk', 'public');
        $mediaPath = config('admin.mediaPath', 'media');
        
        // pathフィールドがファイル名のみの場合とフルパスの場合を考慮
        if (strpos($media->path, $mediaPath) === 0) {
            // フルパスが保存されている場合
            $filePath = $media->path;
        } else {
            // ファイル名のみが保存されている場合
            $filePath = $mediaPath . '/' . $media->path;
        }

        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
        }

        $media->delete();

        return redirect()->route('admin.media.index')->with('success', 'File deleted successfully.');
    }

    public function download(Media $media)
    {
        $disk = config('admin.storageDisk', 'public');
        $mediaPath = config('admin.mediaPath', 'media');
        
        // pathフィールドがファイル名のみの場合とフルパスの場合を考慮
        if (strpos($media->path, $mediaPath) === 0) {
            // フルパスが保存されている場合
            $filePath = $media->path;
        } else {
            // ファイル名のみが保存されている場合
            $filePath = $mediaPath . '/' . $media->path;
        }

        if (!Storage::disk($disk)->exists($filePath)) {
            abort(404, __('admin/media.error.file_not_exists'));
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
        $mediaPath = config('admin.mediaPath', 'media');
        if (strpos($media->path, $mediaPath) === 0) {
            // フルパスが保存されている場合
            $filePath = storage_path('app/' . config('admin.storageDisk') . '/' . $media->path);
        } else {
            // ファイル名のみが保存されている場合
            $filePath = storage_path('app/' . config('admin.storageDisk') . '/' . $mediaPath . '/' . $media->path);
        }

        if (!file_exists($filePath)) {
            abort(404, 'ファイルが存在しません');
        }

        $this->viewParams['media'] = $media;
        $this->viewParams['filePath'] = $filePath;

        return view('admin.media.preview', $this->viewParams);
    }

    /**
     * メディア情報を更新
     */
    public function updateMedia(Request $request, Media $media)
    {
        $request->validate([
            'caption' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->mediaRepository->update($media->id, [
            'caption' => $request->input('caption'),
            'alt_text' => $request->input('alt_text'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('admin.media.preview', $media->id)
            ->with('success', 'メディア情報が更新されました。');
    }


    public function settings()
    {
        $allowedFileTypes = $this->mediaSettingRepository->get('allowed_file_types', []);
        $maxFileSize = $this->mediaSettingRepository->get('max_file_size', '2048');

        $fileExtensions = config('admin.fileExtensions');
        $fileExtensionNames = config('admin.fileExtensionNames');

        // セキュリティ設定を取得
        $securitySettings = $this->mediaSecurityService->getSecuritySettings();

        $this->viewParams['allowedFileTypes'] = $allowedFileTypes;
        $this->viewParams['maxFileSize'] = $maxFileSize;
        $this->viewParams['fileExtensions'] = $fileExtensions;
        $this->viewParams['fileExtensionNames'] = $fileExtensionNames;
        $this->viewParams['securitySettings'] = $securitySettings;

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
        $mediaPath = config('admin.mediaPath', 'media');
        $media->getCollection()->transform(function($item) use ($mediaPath) {
            // pathフィールドがファイル名のみの場合とフルパスの場合を考慮
            if (strpos($item->path, $mediaPath) === 0) {
                $item->url = asset('storage/' . $item->path);
            } else {
                $item->url = asset('storage/' . $mediaPath . '/' . $item->path);
            }
            return $item;
        });
        
        return response()->json([
            'success' => true,
            'media' => $media
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function update(Request $request)
    {
        $fileExtensions = config('admin.fileExtensions');

        $request->validate([
            'allowed_file_types' => 'array',
            'allowed_file_types.*' => 'in:' . implode(',', $fileExtensions),
            'max_file_size' => 'required|integer|min:1|max:100', // 1MB to 100MB（レガシー互換用）
            // ファイルタイプ別サイズ上限（MB）
            'max_file_size_image' => 'required|integer|min:1|max:100',
            'max_file_size_video' => 'required|integer|min:1|max:1000',
            'max_file_size_document' => 'required|integer|min:1|max:100',
            'max_file_size_archive' => 'required|integer|min:1|max:500',
            // セキュリティ設定
            'svg_sanitization_enabled' => 'boolean',
            'zip_security_enabled' => 'boolean',
            'mime_validation_enabled' => 'boolean',
            // ZIP詳細設定
            'zip_max_compression_ratio' => 'required|integer|min:10|max:1000',
            'zip_max_file_count' => 'required|integer|min:10|max:10000',
        ]);

        $selectedTypes = $request->input('allowed_file_types', []);
        $maxFileSizeMB = $request->input('max_file_size');
        $maxFileSize = round($maxFileSizeMB * 1024); // Convert MB to KB for storage

        // 基本設定
        $this->mediaSettingRepository->set('allowed_file_types', $selectedTypes);
        $this->mediaSettingRepository->set('max_file_size', $maxFileSize);

        // ファイルタイプ別サイズ上限（MBからKBに変換）
        $this->mediaSettingRepository->set('max_file_size_image', round($request->input('max_file_size_image') * 1024));
        $this->mediaSettingRepository->set('max_file_size_video', round($request->input('max_file_size_video') * 1024));
        $this->mediaSettingRepository->set('max_file_size_document', round($request->input('max_file_size_document') * 1024));
        $this->mediaSettingRepository->set('max_file_size_archive', round($request->input('max_file_size_archive') * 1024));

        // セキュリティ設定
        $this->mediaSettingRepository->set('svg_sanitization_enabled', $request->boolean('svg_sanitization_enabled') ? '1' : '0');
        $this->mediaSettingRepository->set('zip_security_enabled', $request->boolean('zip_security_enabled') ? '1' : '0');
        $this->mediaSettingRepository->set('mime_validation_enabled', $request->boolean('mime_validation_enabled') ? '1' : '0');

        // ZIP詳細設定
        $this->mediaSettingRepository->set('zip_max_compression_ratio', $request->input('zip_max_compression_ratio'));
        $this->mediaSettingRepository->set('zip_max_file_count', $request->input('zip_max_file_count'));

        return redirect()->back()->with('success', __('admin/media.success.settings_updated'));
    }
}
