@extends('admin::partials.layout')

@section('content')

    <pre>{{ print_r(session()->all(), true) }}</pre>

    <!-- Flash Message -->
    @include('components::flash_message')

    <!-- テーマ一覧 -->
    <div class="max-w-4xl mx-auto mt-12">
        <h2 class="text-2xl font-bold mb-4">利用可能なテーマ</h2>

        <!-- デフォルトテーマ -->
        @if ($defaultTheme)
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-4 relative">
                <h3 class="text-lg font-bold mb-2">{{ $defaultTheme->name }} <span class="text-sm text-gray-500">({{ $defaultTheme->version }})</span></h3>

                @if ($defaultTheme->id === getActiveTheme())
                        <span class="text-green-500 font-semibold">現在使用中</span>
                @else
                    <!-- 有効化ボタン -->
                    <form action="{{ route('admin.contents.themes.activate', $defaultTheme->id) }}" method="POST" class="inline-block">
                        @csrf
                        <button type="submit" onclick="return confirm('このテーマを有効化しますか？')" class="text-white bg-blue-500 hover:bg-blue-600 font-medium rounded-lg text-sm px-4 py-2 mr-2">
                            有効化
                        </button>
                    </form>
                @endif
            </div>
        @endif

        <!-- 他のテーマ一覧 -->
        <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2">
            @foreach ($themes as $theme)
                <div class="bg-white  dark:bg-gray-800 shadow-md rounded-lg p-6 relative">
                    <h3 class="text-lg font-bold mb-2">{{ $theme->name }} <span class="text-sm text-gray-500">({{ $theme->version }})</span></h3>




                    @if ($theme->id === getActiveTheme())
                        <span class="text-green-500 font-semibold">現在使用中</span>
                    @else
                        <!-- 有効化ボタン -->
                        <form action="{{ route('admin.contents.themes.activate', $theme->id) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit" onclick="return confirm('このテーマを有効化しますか？')" class="text-white bg-blue-500 hover:bg-blue-600 font-medium rounded-lg text-sm px-4 py-2 mr-2">
                                有効化
                            </button>
                        </form>
                        <!-- 削除ボタン -->
                        <form action="{{ route('admin.contents.themes.delete', $theme->slug) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit" onclick="return confirm('本当に削除しますか？')" class="text-white bg-red-500 hover:bg-red-600 font-medium rounded-lg text-sm px-4 py-2">
                                削除
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- ページネーション -->
        <div class="mt-6">
            {{ $themes->links('pagination::tailwind') }}
        </div>
    </div>

@endsection
