<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Middleware;

use App\Services\Csp\CspBuilder;
use App\Services\Csp\CspExtensionLoader;
use App\Services\Csp\CspNonceGenerator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Security Policy Middleware
 *
 * CSPヘッダーをレスポンスに付与するミドルウェア。
 */
class ContentSecurityPolicy
{
    protected CspBuilder $builder;

    protected CspNonceGenerator $nonceGenerator;

    protected CspExtensionLoader $extensionLoader;

    protected bool $extensionsLoaded = false;

    public function __construct(
        CspBuilder $builder,
        CspNonceGenerator $nonceGenerator,
        CspExtensionLoader $extensionLoader
    ) {
        $this->builder = $builder;
        $this->nonceGenerator = $nonceGenerator;
        $this->extensionLoader = $extensionLoader;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $context = null): Response
    {
        // CSPが無効な場合はスキップ
        if (! $this->builder->isEnabled()) {
            return $next($request);
        }

        // 除外パスのチェック
        if ($this->isExcludedPath($request)) {
            return $next($request);
        }

        // nonceをリクエストに保存（Bladeで使用するため）
        $request->attributes->set('csp_nonce', $this->nonceGenerator->getNonce());

        // プラグイン・テーマからCSP設定を読み込み（1回のみ）
        if (! $this->extensionsLoaded) {
            $this->extensionLoader->loadAll();
            $this->extensionsLoaded = true;
        }

        // コンテキストを設定（admin/front）
        $context = $context ?? $this->detectContext($request);
        $this->builder->setContext($context);

        // レスポンスを取得
        $response = $next($request);

        // HTMLレスポンスのみにCSPヘッダーを付与
        if ($this->shouldAddCspHeader($response)) {
            // Laravel Boostが挿入するスクリプトにnonceを追加（開発環境のみ）
            if (! app()->environment('production')) {
                $this->addNonceToBoostScripts($response);
            }

            $headerName = $this->builder->getHeaderName();
            $headerValue = $this->builder->build();

            // セーフモード等で空文字が返った場合はヘッダーを送出しない。
            // 空文字のCSPはブラウザによって「無視」または「全拒否」と解釈されるため、
            // ヘッダー自体を付与しないことで既定のブラウザ挙動に委ねる。
            if ($headerValue !== '') {
                $response->headers->set($headerName, $headerValue);
            }

            // 追加のセキュリティヘッダー（CSPが無効でも付与する）
            $this->addSecurityHeaders($response);

            // ルート単位の frame-ancestors 上書き（管理画面内iframeプレビュー用）
            if ($headerValue !== '') {
                $this->overrideFrameAncestorsIfRequested($request, $response, $headerName);
            }
        }

        return $response;
    }

    /**
     * 除外パスかどうかをチェック
     */
    protected function isExcludedPath(Request $request): bool
    {
        $excludedPaths = config('csp.excluded_paths', []);
        $path = $request->path();

        foreach ($excludedPaths as $pattern) {
            // ワイルドカードパターンを正規表現に変換
            $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
            if (preg_match("/^{$regex}$/", $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * コンテキストを自動検出
     */
    protected function detectContext(Request $request): string
    {
        $path = $request->path();

        // 管理画面パスの判定
        $adminPath = config('admin.path', 'admin');
        if (str_starts_with($path, $adminPath) || str_starts_with($path, 'admin')) {
            return 'admin';
        }

        return 'front';
    }

    /**
     * CSPヘッダーを追加すべきかどうか
     */
    protected function shouldAddCspHeader(Response $response): bool
    {
        // ステータスコードが成功系でない場合はスキップ
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        // Content-Typeがtext/htmlの場合のみ
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html') || empty($contentType)) {
            return true;
        }

        return false;
    }

    /**
     * Laravel Boostが挿入するスクリプトにnonceを追加
     *
     * 開発環境でLaravel Boost（MCP Server）が動的に挿入する
     * browser-logger-activeスクリプトにCSP nonceを付与する。
     */
    protected function addNonceToBoostScripts(Response $response): void
    {
        $content = $response->getContent();

        if ($content === false || empty($content)) {
            return;
        }

        $nonce = $this->nonceGenerator->getNonce();

        // <script id="browser-logger-active"> にnonceを追加
        $pattern = '/<script\s+id=["\']browser-logger-active["\']\s*>/i';
        $replacement = '<script id="browser-logger-active" nonce="'.$nonce.'">';

        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent !== null && $newContent !== $content) {
            $response->setContent($newContent);
        }
    }

    /**
     * リクエスト属性に基づいて frame-ancestors ディレクティブを上書き
     *
     * 管理画面内でiframeプレビューを使用するルートで、
     * frame-ancestors を 'none' から 'self' に変更する。
     * コントローラーで request()->attributes->set('csp_frame_ancestors_self', true) を設定する。
     */
    protected function overrideFrameAncestorsIfRequested(Request $request, Response $response, string $headerName): void
    {
        if (! $request->attributes->get('csp_frame_ancestors_self')) {
            return;
        }

        $cspHeader = $response->headers->get($headerName, '');
        if (empty($cspHeader)) {
            return;
        }

        $updatedHeader = preg_replace(
            "/frame-ancestors\s+'none'/",
            "frame-ancestors 'self'",
            $cspHeader
        );

        if ($updatedHeader !== $cspHeader) {
            $response->headers->set($headerName, $updatedHeader);
        }
    }

    /**
     * 追加のセキュリティヘッダーを付与
     */
    protected function addSecurityHeaders(Response $response): void
    {
        // X-Content-Type-Options: MIMEタイプスニッフィング防止
        if (! $response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        // X-Frame-Options: クリックジャッキング防止（CSPのframe-ancestorsと併用）
        if (! $response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Referrer-Policy: リファラー情報の制御
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // Permissions-Policy: ブラウザ機能の制限
        if (! $response->headers->has('Permissions-Policy')) {
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        }
    }
}
