import { defineConfig } from 'vite';
import path from 'path';
import laravel from 'laravel-vite-plugin';
import liveReload from 'vite-plugin-live-reload'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/src/admin/js/app.js',
                'resources/src/admin/scss/style.scss',
                'resources/src/common/js/app.js',
                'resources/src/common/scss/style.scss',
            ],
            refresh: true,
        }),
        liveReload([
            __dirname + '/app/**/*.php',
            __dirname + '/config/**/*.php',
            __dirname + '/database/**/*.php',
            __dirname + '/lang/**/*.php',
            __dirname + '/routes/**/*.php',
            __dirname + '/stubs/**/*.stub',
            __dirname + '/resources/src/**/*.php',
            __dirname + '/resources/src/**/*.scss',
            __dirname + '/resources/src/**/*.js',
            __dirname + '/plugins/**/**/*.php',
            __dirname + '/plugins/**/**/*.js',
            __dirname + '/plugins/**/**/*.scss',
            __dirname + '/themes/**/**/*.php',
            __dirname + '/themes/**/**/*.js',
            __dirname + '/themes/**/**/*.scss',

        ]),
    ],
    build: {
        manifest: 'manifest.json', // マニフェストファイルの出力先
        outDir: 'public/assets/build', // 出力先ディレクトリ
        rollupOptions: {
            input: {
                admin_js: path.resolve(__dirname, 'resources/src/admin/js/app.js'),
                admin_css: path.resolve(__dirname, 'resources/src/admin/scss/style.scss'),
                common_js: path.resolve(__dirname, 'resources/src/common/js/app.js'),
                common_css: path.resolve(__dirname, 'resources/src/common/scss/style.scss'),
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
        watch: {
            usePolling: true,     // ポーリングでファイル変更を検知
            interval: 100,        // ポーリングの間隔（お好みで調整）
        },
        // HMRの設定
        hmr: {
            host: 'localhost',    // ブラウザがアクセスするホスト(ホストOSから見た名前)
            port: 5173,
            // protocol: 'wss',    // HTTPS/WSS を使いたい場合は有効化
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/src'),
        },
    },
});
