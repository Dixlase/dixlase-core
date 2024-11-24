<x-admin-layout :title="__($title)">
    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="shadow-md rounded p-6">
            <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">名前</label>
                    <input type="text" name="name" id="name" required
                           class="{{ config('admin.form_class.text') }} {{ config('admin.theme_class.' . $theme . '.form_input_text') }}"
                           value="{{ old('name', $user->name) }}">
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                    <input type="email" name="email" id="email" required
                           class="{{ config('admin.form_class.text') }} {{ config('admin.theme_class.' . $theme . '.form_input_text') }}"
                           value="{{ old('email', $user->email) }}">
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700">
                        更新
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
