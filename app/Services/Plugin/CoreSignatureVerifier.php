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

namespace App\Services\Plugin;

use App\Contracts\Plugin\SignatureVerifierInterface;
use App\DTO\Plugin\SignatureVerificationResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * コア側署名検証スタブ
 *
 * DixlaseDevKit プラグインが未インストールの場合に使用されます。
 * signature.sig ファイルの存在確認とメタデータ読み取りのみを行い、
 * 実際の暗号学的検証は行いません。
 *
 * DixlaseDevKit がインストール済みの場合は、プラグイン側の
 * アダプターが SignatureVerifierInterface にバインドされ、
 * Ed25519 ベースの検証が行われます。
 */
class CoreSignatureVerifier implements SignatureVerifierInterface
{
    /**
     * プラグインの署名を検証する
     *
     * スタブ実装のため、署名ファイルの存在確認とメタデータ読み取りのみ行う。
     * 暗号学的検証は実行しない。
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

        // signing セクションがない場合
        if (! isset($pluginData['signing'])) {
            return SignatureVerificationResult::unsigned();
        }

        $signing = $pluginData['signing'];
        $keyId = $signing['key_id'] ?? null;

        // signature.sig が存在しない場合
        if (! File::exists($signaturePath)) {
            return SignatureVerificationResult::unsigned('署名宣言はありますが、署名ファイルが見つかりません。');
        }

        // 署名ファイルのメタデータを読み取る
        $sigContent = File::get($signaturePath);
        $sigData = json_decode($sigContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return SignatureVerificationResult::error('署名ファイルの解析に失敗しました。');
        }

        // 検証モジュールが利用できないため、pending を返す
        return new SignatureVerificationResult(
            status: SignatureVerificationResult::STATUS_PENDING,
            type: $this->determineSignatureType($sigData['key_id'] ?? $keyId),
            signedBy: $sigData['signed_by'] ?? null,
            signedAt: $sigData['signed_at'] ?? null,
            keyId: $sigData['key_id'] ?? $keyId,
            message: '署名検証モジュール（DixlaseDevKit）が利用できないため、検証を保留中です。',
        );
    }

    /**
     * 署名検証が利用可能かどうか
     *
     * コアスタブでは暗号学的検証ができないため常に false を返す。
     */
    public function isAvailable(): bool
    {
        return false;
    }

    /**
     * 署名タイプを判定
     */
    protected function determineSignatureType(?string $keyId): ?string
    {
        if ($keyId === null) {
            return null;
        }

        if (str_starts_with($keyId, 'dixlase-official')) {
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
