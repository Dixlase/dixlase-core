import { defineConfig } from 'vite';
import path from 'path';
import laravel from 'laravel-vite-plugin';
import liveReload from 'vite-plugin-live-reload'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/src/admin/js/app.js',
                'resources/src/admin/scss/app.scss',
                'resources/src/common/js/app.js',
                'resources/src/common/scss/app.scss',
            ],
            refresh: true,
        }),
        liveReload([
			__dirname + '/**/*.php',
			__dirname + '/resources/src/**/*.scss',
			__dirname + '/resources/src/**/*.js',
		]),
    ],
    build: {
        manifest: 'manifest.json', // マニフェストファイルの出力先
		outDir: 'public/assets/common', // 出力先ディレクトリ
        rollupOptions: {
            input: {
                adminJs: path.resolve(__dirname, 'resources/src/admin/js/app.js'),
                adminScss: path.resolve(__dirname, 'resources/src/admin/scss/app.scss'),
                common: path.resolve(__dirname, 'resources/src/common/js/app.js'),
                commonScss: path.resolve(__dirname, 'resources/src/common/scss/app.scss'),
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
        host: '0.0.0.0',
        open:false,
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/src'),
        },
    },
});
