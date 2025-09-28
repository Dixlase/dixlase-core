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

        //メディアをページネーションで読み込み（メンバー情報も事前読み込み）
        $media = Media::with('member')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString(); // URLパラメータを保持
            
        $this->viewParams['media'] = $media;

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


    public function settings()
    {

        $allowedFileTypes = MediaSetting::where('name', 'allowed_file_types')->value('value');
        $allowedFileTypes = json_decode($allowedFileTypes, true) ?? [];

        $maxFileSize = MediaSetting::where('name', 'max_file_size')->value('value') ?? '2048';

        $fileExtensions = config('admin.fileExtensions');

        $this->viewParams['allowedFileTypes'] = $allowedFileTypes;
        $this->viewParams['maxFileSize'] = $maxFileSize;
        $this->viewParams['fileExtensions'] = $fileExtensions;

        return view('admin.media.settings', $this->viewParams);
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
