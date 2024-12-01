@extends('admin.partials.layout')

@section('content')

        <!-- Flash message for success or error -->
        @include('components.flash_message')

        <!-- Pages table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">#</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Title</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">URL</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Created At</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-700">{{ $page->id }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $page->title }}</td>
                            <td class="px-4 py-2 text-blue-600 underline">
                                <a href="{{ url($pages_directory . '/' . $page->slug) }}" target="_blank">{{ url($pages_directory . '/' . $page->slug) }}</a>
                            </td>
                            <td class="px-4 py-2 text-gray-700">{{ $page->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 flex items-center space-x-2">
                                <a href="{{ route('admin.contents.pages.edit', $page->id) }}" class="bg-yellow-400 hover:bg-yellow-500 text-white text-sm font-bold py-1 px-3 rounded">
                                    Edit
                                </a>
                                <form action="{{ route('admin.contents.pages.destroy', $page->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-1 px-3 rounded" onclick="return confirm('Are you sure you want to delete this page?')">
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

        <!-- Pagination links -->
        <div class="mt-6">
            {{ $pages->links('pagination::tailwind') }}
        </div>
@endsection
