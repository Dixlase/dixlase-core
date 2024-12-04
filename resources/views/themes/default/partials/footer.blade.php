<footer class="bg-gray-800 text-white py-6">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 上部リンク -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <!-- カラム1 -->
            <div>
                <h5 class="text-lg font-semibold mb-4">会社情報</h5>
                <ul class="space-y-2">
                    <li><a href="/about" class="hover:underline">会社概要</a></li>
                    <li><a href="/privacy" class="hover:underline">プライバシーポリシー</a></li>
                    <li><a href="/terms" class="hover:underline">利用規約</a></li>
                </ul>
            </div>
            <!-- カラム2 -->
            <div>
                <h5 class="text-lg font-semibold mb-4">サポート</h5>
                <ul class="space-y-2">
                    <li><a href="/faq" class="hover:underline">よくある質問</a></li>
                    <li><a href="/support" class="hover:underline">お問い合わせ</a></li>
                </ul>
            </div>
            <!-- カラム3 -->
            <div>
                <h5 class="text-lg font-semibold mb-4">サービス</h5>
                <ul class="space-y-2">
                    <li><a href="/services" class="hover:underline">サービス一覧</a></li>
                    <li><a href="/pricing" class="hover:underline">料金プラン</a></li>
                </ul>
            </div>
            <!-- カラム4 -->
            <div>
                <h5 class="text-lg font-semibold mb-4">フォローする</h5>
                <div class="flex space-x-4">
                    <a href="https://facebook.com" target="_blank" class="hover:underline">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="https://twitter.com" target="_blank" class="hover:underline">
                        <i class="fab fa-twitter"></i> Twitter
                    </a>
                    <a href="https://instagram.com" target="_blank" class="hover:underline">
                        <i class="fab fa-instagram"></i> Instagram
                    </a>
                </div>
            </div>
        </div>

        <!-- 下部コピーライト -->
        <div class="text-center border-t border-gray-700 pt-6">
            <p class="text-sm">&copy; {{ date('Y') }} YourCompanyName. All rights reserved.</p>
        </div>
    </div>
</footer>
