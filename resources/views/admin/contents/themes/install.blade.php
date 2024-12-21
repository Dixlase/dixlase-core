

@extends('admin::partials.layout')

@section('content')

    <!-- Flash Message -->
    @include('components::flash_message')

    <!-- ファイルアップロードフォーム -->
    <div class="max-w-2xl mx-auto mt-10 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4">テーマをアップロード</h2>
        <form action="{{ route('admin.contents.themes.upload') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="theme">ファイルを選択</label>
                <input type="file" name="theme" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>
            <button type="submit" class="bg-indigo-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-indigo-700 transition duration-300">
                アップロード
            </button>
        </form>
    </div>


@endsection

