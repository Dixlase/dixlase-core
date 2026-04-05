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
        ]),
    ],
    base: command === 'build' ? '/assets/build/' : '/', // 本番環境でのアセットのベースパス
    build: {
        manifest: 'manifest.json', // マニフェストファイルの出力先
        outDir: 'public/assets/build', // 出力先ディレクトリ
        assetsDir: '.', // アセットディレクトリ（outDir相対）
        rollupOptions: {
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
                // JavaScriptファイルの名前を指定
                entryFileNames: 'js/[name].js',  // [name]は元のファイル名に対応
                chunkFileNames: 'js/[name].js',  // 他のチャンクファイルも指定
                // CSSファイルの名前を指定
                assetFileNames: ({ name }) => {
                    const extension = (name?.split('.').pop() ?? '').toLowerCase(); // 拡張子を取得して小文字化
                    if (/\.css$/.test(name ?? '')) {
                        return 'css/[name][extname]';  // CSSは特定のフォルダに保存
                    }
                    return `${extension}/[name][extname]`; // その他のアセットはassetsフォルダに
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
            ],
        },
        // HMRの設定
        hmr: {
            host: 'localhost',    // ブラウザがアクセスするホスト(ホストOSから見た名前)
            port: 5173,
            protocol: 'wss',      // HTTPS/WSS を使用
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/src'),
        },
    },
}));
