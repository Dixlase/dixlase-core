<?php
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Csp\CspBuilder;
use App\Services\Csp\CspNonceGenerator;
use App\Services\Csp\CspExtensionLoader;

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
        if (!$this->builder->isEnabled()) {
            return $next($request);
        }

        // 除外パスのチェック
        if ($this->isExcludedPath($request)) {
            return $next($request);
        }

        // nonceをリクエストに保存（Bladeで使用するため）
        $request->attributes->set('csp_nonce', $this->nonceGenerator->getNonce());

        // プラグイン・テーマからCSP設定を読み込み（1回のみ）
        if (!$this->extensionsLoaded) {
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
            $headerName = $this->builder->getHeaderName();
            $headerValue = $this->builder->build();
            
            $response->headers->set($headerName, $headerValue);

            // 追加のセキュリティヘッダー
            $this->addSecurityHeaders($response);
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
     * 追加のセキュリティヘッダーを付与
     */
    protected function addSecurityHeaders(Response $response): void
    {
        // X-Content-Type-Options: MIMEタイプスニッフィング防止
        if (!$response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        // X-Frame-Options: クリックジャッキング防止（CSPのframe-ancestorsと併用）
        if (!$response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Referrer-Policy: リファラー情報の制御
        if (!$response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // Permissions-Policy: ブラウザ機能の制限
        if (!$response->headers->has('Permissions-Policy')) {
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        }
    }
}
