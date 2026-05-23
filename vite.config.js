import { defineConfig } from 'vite';
import path from 'path';
import fs from 'fs';
import laravel from 'laravel-vite-plugin';
import liveReload from 'vite-plugin-live-reload'

export default defineConfig(({ command }) => ({
    plugins: [
        laravel({
            input: [
                'resources/src/common/css/tailwind.css',
                'resources/src/admin/js/app.js',
                'resources/src/admin/scss/style.scss',
                'resources/src/common/js/app.js',
                'resources/src/common/scss/style.scss',
                'resources/src/auth/js/dark-mode.js',
                'resources/src/install/js/dark-mode-init.js',
                'resources/src/install/js/app.js',
                'resources/src/install/scss/style.scss',
                'resources/src/components/mail-server/js/dark-mode.js',
                'resources/src/components/mail-server/js/verification-success.js',
                'resources/src/components/mail-server/js/verification-error.js',
            ],
            refresh: [
                // デフォルトのBladeテンプレート
                'resources/views/**',
                // ストレージ内のコンテンツファイル
                'storage/app/private/plugins/**',
                'storage/app/private/themes/**',
                'storage/app/private/front/**',
            ],
        }),
        liveReload([
            // コアファイル
            __dirname + '/app/**/*.php',
            __dirname + '/config/**/*.php',
            __dirname + '/database/**/*.php',
            __dirname + '/lang/**/*.php',
            __dirname + '/routes/**/*.php',
            __dirname + '/stubs/**/*.stub',
            __dirname + '/resources/src/**/*.php',
            __dirname + '/resources/src/**/*.scss',
            __dirname + '/resources/src/**/*.js',
            // プラグイン
            __dirname + '/plugins/**/**/*.php',
            __dirname + '/plugins/**/**/*.js',
            __dirname + '/plugins/**/**/*.scss',
            // テーマ
            __dirname + '/themes/**/**/*.php',
            __dirname + '/themes/**/**/*.js',
            __dirname + '/themes/**/**/*.scss',
            // ストレージ内のコンテンツファイル（Blade, HTML, Markdown）
            __dirname + '/storage/app/private/plugins/**/*.blade.php',
            __dirname + '/storage/app/private/plugins/**/*.html',
            __dirname + '/storage/app/private/plugins/**/*.md',
            __dirname + '/storage/app/private/themes/**/*.blade.php',
            __dirname + '/storage/app/private/themes/**/*.html',
            __dirname + '/storage/app/private/themes/**/*.md',
            __dirname + '/storage/app/private/front/**/*.blade.php',
            __dirname + '/storage/app/private/front/**/*.html',
            __dirname + '/storage/app/private/front/**/*.md',
        ], {
            // Laravel が動的にコンパイルする中間ファイル群を除外
            // （ブラウザがメニュー遷移中にこれらが書き換わると、
            //  Vite の WebSocket リロード信号で navigation がキャンセルされる）
            ignored: [
                '**/storage/framework/**',
                '**/bootstrap/cache/**',
                '**/node_modules/**',
                '**/vendor/**',
                '**/.git/**',
            ],
        }),
    ],
    base: command === 'build' ? '/assets/build/' : '/', // 本番環境でのアセットのベースパス
    build: {
        manifest: 'manifest.json', // マニフェストファイルの出力先
        outDir: 'public/assets/build', // 出力先ディレクトリ
        assetsDir: '.', // アセットディレクトリ（outDir相対）

        // CSS の minify は lightningcss を使う。
        // 共通 SCSS の `:where(.dark, .dark *) &:before { ... }` のような
        // ルールに対し、Tailwind v4 が出力する CSS では空の `:where()`
        // が生成され、Vite の CSS minify 既定である esbuild がパース
        // できず毎ビルドで `Unexpected ")"` の警告を出す
        // (`@tailwindcss/typography` 0.5.x も同様の空 :where() を出す)。
        // lightningcss は forgiving-selector-list を素直に受け入れる
        // (Tailwind v4 公式推奨)。
        cssMinify: 'lightningcss',

        // lightningcss は明示しないと保守的な browserslist 既定で動き、
        // Tailwind v4 が前提とするモダン CSS (oklch() / nesting /
        // `:where()` など) に対して fallback を展開してファイルサイズが
        // 膨れる。Tailwind v4 自身が想定するモダンブラウザに揃え、
        // 不要な変換を避ける。
        cssTarget: ['chrome111', 'edge111', 'safari16.4', 'firefox128'],

        rollupOptions: {
            treeshake: {
                moduleSideEffects: true,
            },
            input: {
                tailwind: path.resolve(__dirname, 'resources/src/common/css/tailwind.css'),
                admin_js: path.resolve(__dirname, 'resources/src/admin/js/app.js'),
                admin_css: path.resolve(__dirname, 'resources/src/admin/scss/style.scss'),
                common_js: path.resolve(__dirname, 'resources/src/common/js/app.js'),
                common_css: path.resolve(__dirname, 'resources/src/common/scss/style.scss'),
                'auth-dark-mode': path.resolve(__dirname, 'resources/src/auth/js/dark-mode.js'),
                'install-dark-mode-init': path.resolve(__dirname, 'resources/src/install/js/dark-mode-init.js'),
                install_js: path.resolve(__dirname, 'resources/src/install/js/app.js'),
                install_css: path.resolve(__dirname, 'resources/src/install/scss/style.scss'),
                'mail-server-dark-mode': path.resolve(__dirname, 'resources/src/components/mail-server/js/dark-mode.js'),
                'mail-server-verification-success': path.resolve(__dirname, 'resources/src/components/mail-server/js/verification-success.js'),
                'mail-server-verification-error': path.resolve(__dirname, 'resources/src/components/mail-server/js/verification-error.js'),
            },
            output: {
                // JavaScript ファイル名。入力キーの末尾 `_js` は出力時に剥がす
                // (例: `admin_js` → `js/admin.js`)。
                entryFileNames: ({ name }) => {
                    const cleaned = (name ?? '').replace(/_js$/, '');
                    return `js/${cleaned}.js`;
                },
                chunkFileNames: 'js/[name].js',  // 動的 import 等のチャンクファイル
                // CSS / その他アセットのファイル名。CSS は末尾 `_css` を剥がして出力
                // (例: `admin_css.css` → `css/admin.css`)。
                assetFileNames: ({ name }) => {
                    const safeName = name ?? '';
                    if (/\.css$/.test(safeName)) {
                        const cleaned = safeName.replace(/_css\.css$/, '.css');
                        return `css/${cleaned}`;
                    }
                    const extension = (safeName.split('.').pop() ?? '').toLowerCase();
                    return `${extension}/${safeName}`;
                },
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: "modern-compiler",
            },
        },
    },
    server: {
        host: '0.0.0.0',        // Docker コンテナ内で全てのインターフェースをバインド
        port: 5173,
        strictPort: true,       // ポートが使用中なら失敗する
        https: command === 'serve' ? {
            key: fs.readFileSync('/etc/ssl/private/localhost.key'),
            cert: fs.readFileSync('/etc/ssl/private/localhost.crt'),
        } : false,
        watch: {
            usePolling: true,     // ポーリングでファイル変更を検知
            interval: 100,        // ポーリングの間隔（お好みで調整）
            ignored: [
                '**/node_modules/**',
                '**/.git/**',
                '**/.env',        // .envファイルの監視を無効化（インストール中の頻繁な更新でクラッシュ防止）
                '**/storage/framework/cache/**', // キャッシュファイルの監視を無効化（リロードループ防止）
            ],
        },
        // HMR settings.
        // clientPort is the port the BROWSER connects to (host-side mapping).
        // It is read from VITE_HMR_CLIENT_PORT so each environment (Dev/Brand/
        // Docs/Demo/Sandbox) can map its own forwarded host port without
        // touching this file. The container itself still binds on 5173
        // internally; only the URL announced to the browser changes.
        hmr: {
            host: 'localhost',
            clientPort: parseInt(process.env.VITE_HMR_CLIENT_PORT || '5173', 10),
            protocol: 'wss',
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/src'),
        },
    },
}));
