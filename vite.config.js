import { defineConfig } from 'vite';
import path from 'path';
import laravel from 'laravel-vite-plugin';
import liveReload from 'vite-plugin-live-reload'

export default defineConfig({
    base: '/build/',
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        liveReload([
			__dirname + '/**/*.php',
			__dirname + '/resources/scss/**/*.scss',
			__dirname + '/resources/js/**/*.js',
		]),
    ],
    build: {
        manifest: 'manifest.json', // マニフェストファイルの出力先
		outDir: 'public/build', // 出力先ディレクトリ
        rollupOptions: {
            input: {
                app: 'resources/js/app.js',
                style: 'resources/scss/app.scss',
            },
            output: {
                // JavaScriptファイルの名前を指定
                entryFileNames: 'js/[name].js',  // [name]は元のファイル名に対応
                chunkFileNames: 'js/[name].js',  // 他のチャンクファイルも指定
                // CSSファイルの名前を指定
                assetFileNames: ({ name }) => {
                    if (/\.css$/.test(name ?? '')) {
                        return 'css/[name][extname]';  // CSSは特定のフォルダに保存
                    }
                    return 'assets/[name][extname]'; // その他のアセットはassetsフォルダに
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
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});
