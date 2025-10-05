import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    build: {
        // ビルド出力先
        outDir: '../../public/assets/themes/dixlase-default-theme',
        emptyOutDir: true,
        
        // ソースマップ
        sourcemap: process.env.NODE_ENV === 'development',
        
        // ロールアップオプション
        rollupOptions: {
            input: {
                // SCSS (開発時)
                'theme-style': resolve(__dirname, 'resources/src/front/scss/style.scss'),
                
                // JavaScript
                'app': resolve(__dirname, 'resources/assets/js/app.js'),
            },
            output: {
                // 出力ファイル名のパターン
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    // CSSファイル
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name][extname]';
                    }
                    // 画像ファイル
                    if (/\.(png|jpe?g|gif|svg|webp|ico)$/.test(assetInfo.name)) {
                        return 'images/[name][extname]';
                    }
                    // フォントファイル
                    if (/\.(woff2?|eot|ttf|otf)$/.test(assetInfo.name)) {
                        return 'fonts/[name][extname]';
                    }
                    // その他
                    return 'assets/[name][extname]';
                },
            },
        },
        
        // マニフェストファイル生成
        manifest: true,
        
        // 最小化
        minify: process.env.NODE_ENV === 'production' ? 'terser' : false,
        
        // Terserオプション
        terserOptions: {
            compress: {
                drop_console: process.env.NODE_ENV === 'production',
            },
        },
    },
    
    // 開発サーバー設定
    server: {
        host: '0.0.0.0',
        port: 5174, // デフォルトテーマ専用ポート
        strictPort: false,
        hmr: {
            host: 'localhost',
        },
    },
    
    // プレビューサーバー設定
    preview: {
        port: 5174,
    },
    
    // 依存関係の最適化
    optimizeDeps: {
        include: [],
    },
    
    // CSS設定
    css: {
        devSourcemap: true,
    },
});
