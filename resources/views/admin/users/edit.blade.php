<x-admin-layout :title="__($title)">
    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="shadow-md rounded p-6">
            <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- フォーム -->
                @include('admin.users.partials.form', ['theme' => $theme])

                <!-- 保存ボタン -->
                @include('components.form.button', [
                    'type' => 'button',
                    'label' => 'ユーザーを更新',
                    'onclick' => "openModal('confirmationModal')",
                    'theme' => $theme,
                ])

                <!-- モーダル -->
                @include('components.form.modal', [
                    'id' => 'confirmationModal',
                    'title' => 'ユーザー情報更新の確認',
                    'message' => 'この内容でユーザー情報を更新しますか？',
                    'cancelText' => '戻る',
                    'theme' => $theme
                ])

            </form>
        </div>
    </div>
</x-admin-layout>
