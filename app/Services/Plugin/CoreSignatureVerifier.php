<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace App\Services\Plugin;

use App\Contracts\Plugin\SignatureVerifierInterface;
use App\DTO\Plugin\SignatureVerificationResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * コア標準の署名検証実装
 *
 * Ed25519 ベースの暗号検証をコア単体で完結する。検証用の公開鍵は
 * AuthorityPublicKeyResolver 経由で keys.dixlase.com から取得し、
 * ローカル DB にキャッシュする。これによりユーザー環境（DixlaseDevKit
 * 未インストール）でも署名検証が動作する。
 *
 * 署名フォーマットは PluginSigner（DixlaseSigner プラグイン）が生成する
 * 形式に合わせる:
 *   - signature.sig: { algo, key_id, signature(base64), signed_at }
 *   - 署名対象: plugin.json から signing セクションを除いた canonical JSON
 *     (ksort + JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
 *   - files[]: 各ファイルの sha256 ハッシュ（plugin.json 自身と
 *     .gitignore 除外パターンを除く）
 *
 * 失効鍵 (is_active=false) の扱い: 数学的検証のみ行う。期限切れや
 * 無効化された鍵で署名されたプラグインも、署名と内容が整合していれば
 * VALID を返す。これは「過去に正規署名されたプラグインを継続使用できる」
 * という運用方針に従う。
 */
class CoreSignatureVerifier implements SignatureVerifierInterface
{
    /**
     * 署名対象から除外する組込みパターン。
     *
     * PluginSigner の config/signing.php exclude_patterns と完全に一致させる
     * 必要がある。署名側と検証側で除外集合がズレると "extra"/"missing" の
     * 誤検出になり改ざん扱いされる。
     */
    private const DEFAULT_EXCLUDE_PATTERNS = [
        '.git',
        '.github',
        '.DS_Store',
        '.editorconfig',
        '__MACOSX',
        'signature.sig',
    ];

    public function __construct(
        protected AuthorityPublicKeyResolver $publicKeyResolver,
    ) {}

    /**
     * プラグインの署名を検証する
     */
    public function verify(string $pluginSlug): SignatureVerificationResult
    {
        $pluginName = Str::studly(str_replace('-', '_', $pluginSlug));
        $pluginPath = base_path("plugins/{$pluginName}");
        $pluginJsonPath = "{$pluginPath}/plugin.json";
        $signaturePath = "{$pluginPath}/signature.sig";

        // plugin.json が存在しない場合
        if (! File::exists($pluginJsonPath)) {
            return SignatureVerificationResult::unsigned('plugin.json が見つかりません。');
        }

        $pluginData = json_decode(File::get($pluginJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return SignatureVerificationResult::error('plugin.json の解析に失敗しました。');
        }

        if (! isset($pluginData['signing'])) {
            return SignatureVerificationResult::unsigned();
        }

        $declaredKeyId = $pluginData['signing']['key_id'] ?? null;

        if (! File::exists($signaturePath)) {
            return SignatureVerificationResult::unsigned('署名宣言はありますが、署名ファイルが見つかりません。');
        }

        $sigData = json_decode(File::get($signaturePath), true);
        if (! is_array($sigData)) {
            return SignatureVerificationResult::error('署名ファイルの解析に失敗しました。');
        }

        if (empty($sigData['key_id']) || empty($sigData['signature'])) {
            return SignatureVerificationResult::invalid('署名ファイルに必須フィールドが不足しています。');
        }

        $signatureKeyId = $sigData['key_id'];
        $signedAt = $sigData['signed_at'] ?? null;
        $signedBy = $sigData['signed_by'] ?? null;

        // plugin.json と signature.sig の key_id が一致しているか
        if ($declaredKeyId !== null && $declaredKeyId !== $signatureKeyId) {
            return SignatureVerificationResult::invalid('plugin.json と signature.sig の鍵IDが一致しません。', [
                'plugin_json_key_id' => $declaredKeyId,
                'signature_key_id' => $signatureKeyId,
            ]);
        }

        // 公開鍵を取得
        $publicKey = $this->publicKeyResolver->resolve($signatureKeyId);
        if ($publicKey === null) {
            // ネット不通かつキャッシュ無し → 検証保留扱い
            // （require ポリシー側で保留＝拒否を判断する）
            return new SignatureVerificationResult(
                status: SignatureVerificationResult::STATUS_PENDING,
                type: $this->determineSignatureType($signatureKeyId),
                signedBy: $signedBy,
                signedAt: $signedAt,
                keyId: $signatureKeyId,
                message: '公開鍵を取得できなかったため検証を保留しました。',
            );
        }

        // ファイル改ざん検知
        if (! isset($pluginData['files']) || ! is_array($pluginData['files'])) {
            return SignatureVerificationResult::invalid('plugin.json に files セクションがありません。');
        }

        $tamper = $this->verifyFileHashes($pluginPath, $pluginData['files']);
        if (! $tamper['valid']) {
            return SignatureVerificationResult::invalid('プラグインファイルの改ざんを検出しました。', [
                'mismatched' => $tamper['mismatched'] ?? [],
                'missing' => $tamper['missing'] ?? [],
                'extra' => $tamper['extra'] ?? [],
            ]);
        }

        // Ed25519 署名検証
        try {
            $cryptoValid = $this->verifyEd25519Signature(
                $pluginData,
                $sigData['signature'],
                $publicKey->public_key,
            );
        } catch (\Throwable $e) {
            Log::warning('CoreSignatureVerifier: ed25519 verification error', [
                'key_id' => $signatureKeyId,
                'error' => $e->getMessage(),
            ]);

            return SignatureVerificationResult::error('署名検証中にエラーが発生しました: '.$e->getMessage());
        }

        if (! $cryptoValid) {
            return SignatureVerificationResult::invalid('署名が一致しません。プラグインが改ざんされているか、署名鍵が異なる可能性があります。');
        }

        return SignatureVerificationResult::valid(
            keyId: $signatureKeyId,
            signedBy: $signedBy,
            signedAt: $signedAt,
            type: $this->determineSignatureType($signatureKeyId),
        );
    }

    /**
     * 署名検証が利用可能かどうか
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Ed25519 署名検証
     *
     * 署名対象データは plugin.json から signing セクションを除き、
     * トップレベルおよび files[] を ksort してから JSON 化したもの。
     * PluginSigner::getDataToSign() と完全に一致させる必要がある。
     */
    protected function verifyEd25519Signature(array $pluginData, string $signatureBase64, string $publicKeyEncoded): bool
    {
        $data = $pluginData;
        unset($data['signing']);
        ksort($data);
        if (isset($data['files'])) {
            ksort($data['files']);
        }
        $dataToVerify = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $signatureBinary = base64_decode($signatureBase64);
        $publicKeyBinary = $this->decodeKey($publicKeyEncoded);

        return sodium_crypto_sign_verify_detached($signatureBinary, $dataToVerify, $publicKeyBinary);
    }

    /**
     * "base64:..." または素の base64 文字列をバイナリへデコード
     */
    protected function decodeKey(string $encoded): string
    {
        if (str_starts_with($encoded, 'base64:')) {
            $encoded = substr($encoded, 7);
        }

        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            throw new \RuntimeException('公開鍵の base64 デコードに失敗しました。');
        }

        return $decoded;
    }

    /**
     * plugin.json の files[] と実ファイルのハッシュを照合
     *
     * @param  array<string, string>  $expectedFiles  files[] セクション（path => "algo:hash"）
     * @return array{valid: bool, mismatched: array<string>, missing: array<string>, extra: array<string>}
     */
    protected function verifyFileHashes(string $pluginPath, array $expectedFiles): array
    {
        $actualFiles = $this->collectFileHashes($pluginPath);

        $missing = array_diff_key($expectedFiles, $actualFiles);
        $extra = array_diff_key($actualFiles, $expectedFiles);
        $mismatched = [];

        foreach ($expectedFiles as $relPath => $expectedHash) {
            if (! isset($actualFiles[$relPath])) {
                continue;
            }
            if ($actualFiles[$relPath] !== $expectedHash) {
                $mismatched[] = $relPath;
            }
        }

        return [
            'valid' => empty($missing) && empty($extra) && empty($mismatched),
            'mismatched' => $mismatched,
            'missing' => array_keys($missing),
            'extra' => array_keys($extra),
        ];
    }

    /**
     * プラグインディレクトリ内のファイルハッシュを収集する。
     * plugin.json 自身と signature.sig、.gitignore で除外されるパスは含めない。
     *
     * @return array<string, string> path => "algo:hash"
     */
    protected function collectFileHashes(string $pluginPath): array
    {
        $files = [];
        $excludePatterns = array_unique(array_merge(
            self::DEFAULT_EXCLUDE_PATTERNS,
            $this->parseGitignore($pluginPath),
        ));

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $relPath = str_replace('\\', '/', str_replace($pluginPath.DIRECTORY_SEPARATOR, '', $file->getPathname()));

            // PluginSigner 側と同じ除外ルール:
            //  - plugin.json 自体は files[] から除く（自己ハッシュの循環を避ける）
            //  - DEFAULT_EXCLUDE_PATTERNS と .gitignore のパスは除く
            if ($relPath === 'plugin.json') {
                continue;
            }
            if ($this->matchesExcludePattern($relPath, $excludePatterns)) {
                continue;
            }

            $files[$relPath] = 'sha256:'.hash_file('sha256', $file->getPathname());
        }

        ksort($files);

        return $files;
    }

    /**
     * .gitignore を簡易パース（PluginSigner と同じロジック）
     *
     * @return array<string>
     */
    protected function parseGitignore(string $pluginPath): array
    {
        $path = $pluginPath.'/.gitignore';
        if (! File::exists($path)) {
            return [];
        }

        $patterns = [];
        foreach (explode("\n", File::get($path)) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '!')) {
                continue;
            }
            $patterns[] = ltrim($line, '/');
        }

        return $patterns;
    }

    /**
     * .gitignore パターンに該当するか
     */
    protected function matchesExcludePattern(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') !== false) {
                $regex = '/^'.str_replace(['*', '/'], ['.*', '\/'], $pattern).'$/';
                if (preg_match($regex, $path)) {
                    return true;
                }
            } elseif (strpos($path, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * key_id から署名タイプを判定（バッジ表示用）
     */
    protected function determineSignatureType(?string $keyId): ?string
    {
        if ($keyId === null) {
            return null;
        }
        if (str_starts_with($keyId, 'dixlase-official') || str_starts_with($keyId, 'dixlase-authority')) {
            return 'official';
        }
        if (str_starts_with($keyId, 'dixlase-verified') || str_starts_with($keyId, 'marketplace')) {
            return 'verified';
        }
        if (str_starts_with($keyId, 'partner-')) {
            return 'partner';
        }

        return null;
    }
}
