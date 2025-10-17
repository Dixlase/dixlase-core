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

class AdminMediaController extends AdminLoggedInController
{
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

        //メディアをページネーションで読み込み（メンバー情報も事前読み込み）
        $query = Media::with('member');

        // 検索条件を適用
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        // ファイルタイプフィルター
        if ($fileType) {
            switch ($fileType) {
                case 'image':
                    $query->where('type', 'like', 'image/%');
                    break;
                case 'video':
                    $query->where('type', 'like', 'video/%');
                    break;
                case 'audio':
                    $query->where('type', 'like', 'audio/%');
                    break;
                case 'document':
                    $query->where(function($q) {
                        $q->where('type', 'like', 'application/%')
                          ->orWhere('type', 'like', 'text/%');
                    });
                    break;
            }
        }

        // アップロードメンバーフィルター
        if ($uploadedBy) {
            $query->where('uploaded_by', $uploadedBy);
        }

        // 日付範囲フィルター
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $media = $query->orderBy($sort, $order)
            ->paginate($perPage)
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
        $allowedFileTypes = MediaSetting::where('name', 'allowed_file_types')->value('value');
        $allowedFileTypes = json_decode($allowedFileTypes, true) ?? [];
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
            return redirect()->back()->withErrors(['file' => 'ファイルが取得できませんでした']);
        }

        //メンバーIDを取得
        $memberId = $this->member->id;

        try {
            $path = $file->store(config('admin.mediaPath'), config('admin.storageDisk'));
            $fileName = basename($path);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file' => 'ファイルの保存に失敗しました']);
        }

        Media::create([
            'name' => $file->getClientOriginalName(),
            'path' => $fileName,
            'type' => $file->getMimeType(),
            'uploaded_by' => $memberId,
        ]);

        return redirect()->route('admin.media.index')->with('success', 'ファイルが正常にアップロードされました。');
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
            abort(404, 'ファイルが存在しません');
        }

        return Storage::disk($disk)->download($filePath, $media->name);
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

        $media->update([
            'caption' => $request->input('caption'),
            'alt_text' => $request->input('alt_text'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('admin.media.preview', $media->id)
            ->with('success', 'メディア情報が更新されました。');
    }


    public function settings()
    {

        $allowedFileTypes = MediaSetting::where('name', 'allowed_file_types')->value('value');
        $allowedFileTypes = json_decode($allowedFileTypes, true) ?? [];

        $maxFileSize = MediaSetting::where('name', 'max_file_size')->value('value') ?? '2048';

        $fileExtensions = config('admin.fileExtensions');
        $fileExtensionNames = config('admin.fileExtensionNames');

        $this->viewParams['allowedFileTypes'] = $allowedFileTypes;
        $this->viewParams['maxFileSize'] = $maxFileSize;
        $this->viewParams['fileExtensions'] = $fileExtensions;
        $this->viewParams['fileExtensionNames'] = $fileExtensionNames;

        return view('admin.media.settings', $this->viewParams);
    }

    /**
     * API: メディア一覧を取得（モーダル用）
     */
    public function api(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $type = $request->get('type');
        
        $query = Media::with('member')->orderBy('created_at', 'desc');
        
        // 検索フィルター
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        // タイプフィルター
        if ($type) {
            switch ($type) {
                case 'image':
                    $query->where('type', 'like', 'image/%');
                    break;
                case 'video':
                    $query->where('type', 'like', 'video/%');
                    break;
                case 'document':
                    $query->whereNotIn('type', function($q) {
                        $q->select('type')->from('media')
                          ->where('type', 'like', 'image/%')
                          ->orWhere('type', 'like', 'video/%');
                    });
                    break;
            }
        }
        
        $media = $query->paginate($perPage);
        
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
            'max_file_size' => 'required|integer|min:1|max:100', // 1MB to 100MB
        ]);

        $selectedTypes = $request->input('allowed_file_types', []);
        $maxFileSizeMB = $request->input('max_file_size');
        $maxFileSize = round($maxFileSizeMB * 1024); // Convert MB to KB for storage

        MediaSetting::updateOrCreate(
            ['name' => 'allowed_file_types'],
            ['value' => json_encode($selectedTypes)]
        );

        MediaSetting::updateOrCreate(
            ['name' => 'max_file_size'],
            ['value' => $maxFileSize]
        );

        return redirect()->back()->with('success', 'メディア設定が更新されました。');
    }
}
