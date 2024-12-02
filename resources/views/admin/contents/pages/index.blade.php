@extends('admin.partials.layout')

@section('content')

        <!-- Flash message for success or error -->
        @include('components.flash_message')


        <!-- デスクトップ用テーブル -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead class="{{ config('admin.appearance_class.table.thead') }}">
                    <tr>
                        <th class="{{ config('admin.appearance_class.table.td') }}">{{ __('common.id') }}</th>
                        <th class="{{ config('admin.appearance_class.table.td') }}">{{ __('common.title') }}</th>
                        <th class="{{ config('admin.appearance_class.table.td') }}">{{ __('common.url') }}</th>
                        <th class="{{ config('admin.appearance_class.table.td') }}">{{ __('common.created_at') }}</th>
                        <th class="{{ config('admin.appearance_class.table.td') }}">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr class="{{ config('admin.appearance_class.table.tr') }}">
                            <td class="{{ config('admin.appearance_class.table.td') }}">{{ $page->id }}</td>
                            <td class="px-4 py-2">{{ $page->title }}</td>
                            <td class="px-4 py-2 text-blue-600 underline">
                                <a href="{{ url($pages_directory . '/' . $page->slug) }}" target="_blank">
                                    {{ url($pages_directory . '/' . $page->slug) }}
                                </a>
                            </td>
                            <td class="px-4 py-2">{{ $page->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 flex items-center space-x-2">
                                <a href="{{ route('admin.contents.pages.edit', ['page' => $page->id]) }}"
                                   class="bg-yellow-400 hover:bg-yellow-500 text-white text-sm font-bold py-1 px-3 rounded">
                                    Edit
                                </a>
                                <form action="{{ route('admin.contents.pages.destroy', ['page' => $page->id]) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-1 px-3 rounded"
                                            onclick="return confirm('Are you sure you want to delete this page?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">No pages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- モバイル用カード -->
        <div class="block md:hidden">
            @forelse($pages as $page)
                <div class="border rounded-lg p-4 mb-4 shadow">
                    <p><strong>ID:</strong> {{ $page->id }}</p>
                    <p><strong>Title:</strong> {{ $page->title }}</p>
                    <p><strong>URL:</strong>
                        <a href="{{ url($pages_directory . '/' . $page->slug) }}" target="_blank" class="text-blue-600 underline">
                            {{ url($pages_directory . '/' . $page->slug) }}
                        </a>
                    </p>
                    <p><strong>Created At:</strong> {{ $page->created_at->format('Y-m-d') }}</p>
                    <div class="mt-2 flex space-x-2">
                        <a href="{{ route('admin.contents.pages.edit', ['page' => $page->id]) }}"
                           class="bg-yellow-400 hover:bg-yellow-500 text-white text-sm font-bold py-1 px-3 rounded">
                            Edit
                        </a>
                        <form action="{{ route('admin.contents.pages.destroy', ['page' => $page->id]) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-1 px-3 rounded"
                                    onclick="return confirm('Are you sure you want to delete this page?')">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500">No pages found.</p>
            @endforelse
        </div>

        <!-- Pagination links -->
        <div class="mt-6">
            {{ $pages->links('pagination::tailwind') }}
        </div>

@endsection
