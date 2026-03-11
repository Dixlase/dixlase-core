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

namespace App\Services\Csp;

/**
 * @api プラグイン/テーマから直接DIで使用可能な安定APIです
 *
 * CSP Nonce Generator
 *
 * リクエストごとに一意のnonceを生成・管理するサービス。
 * nonceはインラインスクリプト/スタイルの許可に使用される。
 */
class CspNonceGenerator
{
    /**
     * 現在のリクエストのnonce値
     */
    protected ?string $nonce = null;

    /**
     * nonce生成時のバイト長
     */
    protected int $nonceLength;

    public function __construct()
    {
        $this->nonceLength = config('csp.nonce_length', 16);
    }

    /**
     * 現在のリクエスト用のnonceを取得
     *
     * まだ生成されていない場合は新規生成する。
     * 同一リクエスト内では常に同じnonceを返す。
     */
    public function getNonce(): string
    {
        if ($this->nonce === null) {
            $this->nonce = $this->generateNonce();
        }

        return $this->nonce;
    }

    /**
     * 新しいnonceを生成
     *
     * 暗号学的に安全なランダムバイトからBase64エンコードされた文字列を生成。
     */
    protected function generateNonce(): string
    {
        $bytes = random_bytes($this->nonceLength);

        return base64_encode($bytes);
    }

    /**
     * nonceをリセット
     *
     * 通常は使用しないが、テスト等で必要な場合に使用。
     */
    public function resetNonce(): void
    {
        $this->nonce = null;
    }

    /**
     * CSPディレクティブ用のnonce文字列を取得
     *
     * 例: 'nonce-abc123...'
     */
    public function getNonceDirective(): string
    {
        return "'nonce-".$this->getNonce()."'";
    }

    /**
     * HTML属性用のnonce文字列を取得
     *
     * 例: nonce="abc123..."
     */
    public function getNonceAttribute(): string
    {
        return 'nonce="'.$this->getNonce().'"';
    }
}
