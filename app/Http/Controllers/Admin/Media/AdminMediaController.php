<?php

/**
 * This file is part of MySoftware.
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
    public function index()
    {
        //メディアをページネーションで読み込み
        $media = Media::paginate(config('admin.perPage'));
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
        $filePath = $mediaPath . '/' . $media->path;

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
        $filePath = $mediaPath . '/' . $media->path;

        if (!Storage::disk($disk)->exists($filePath)) {
            abort(404, 'ファイルが存在しません');
        }

        return Storage::disk($disk)->download($filePath, $media->name);
    }

    public function preview(Media $media)
    {
        $filePath = storage_path('app/' . config('admin.storageDisk') . '/' . config('admin.mediaPath') . '/' . $media->path);

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

        $fileExtensions = config('admin.fileExtensions');

        $this->viewParams['allowedFileTypes'] = $allowedFileTypes;
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
        ]);

        $selectedTypes = $request->input('allowed_file_types', []);

        MediaSetting::updateOrCreate(
            ['name' => 'allowed_file_types'],
            ['value' => json_encode($selectedTypes)]
        );

        return redirect()->back()->with('success', 'メディア設定が更新されました。');
    }
}
