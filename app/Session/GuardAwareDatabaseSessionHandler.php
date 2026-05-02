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

namespace App\Session;

use Illuminate\Session\DatabaseSessionHandler;

class GuardAwareDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * ガード別のセッションテーブル設定
     *
     * @var array
     */
    protected $guardTables = [];

    /**
     * 現在のガード名
     *
     * @var string|null
     */
    protected $currentGuard = null;

    /**
     * ガード判定ロジック（カスタムリゾルバー）
     *
     * @var array
     */
    protected $guardResolvers = [];

    /**
     * 解決済みの管理 URL セグメント（プロセス内キャッシュ）
     *
     * @var string|null
     */
    protected static $resolvedAdminUrl = null;

    /**
     * ガード別のテーブル設定を登録
     */
    public function setGuardTable(string $guard, string $table): void
    {
        $this->guardTables[$guard] = $table;
    }

    /**
     * ガード判定ロジックを登録
     */
    public function addGuardResolver(callable $resolver): void
    {
        $this->guardResolvers[] = $resolver;
    }

    /**
     * 現在のガードに基づいてテーブル名を取得
     */
    protected function getTable(): string
    {
        // 現在の認証ガードを取得
        $guard = $this->getCurrentGuard();

        // ガード別のテーブルが設定されている場合はそれを使用
        if ($guard && isset($this->guardTables[$guard])) {
            return $this->guardTables[$guard];
        }

        // デフォルトのテーブル名を返す
        return $this->table;
    }

    /**
     * 現在のガード名を取得
     */
    protected function getCurrentGuard(): ?string
    {
        // すでに設定されている場合はそれを返す
        if ($this->currentGuard !== null) {
            return $this->currentGuard;
        }

        $path = request()->path();
        $guard = null;

        // カスタムリゾルバーを優先的に実行
        foreach ($this->guardResolvers as $resolver) {
            $guard = $resolver(request());
            if ($guard !== null) {
                $this->currentGuard = $guard;

                return $guard;
            }
        }

        // パスベースのガード判定（優先順位が高い）

        // 管理画面の場合は member ガード。
        // 管理 URL はユーザーが任意にカスタマイズ可能なため、DB から動的に解決した値で照合する。
        // config('admin.url.admin_url') はデフォルト値 'admin' しか返さないため、
        // DB の site_settings.admin_url を静的キャッシュ付きで参照する。
        // これを怠ると admin_url が "admin" 以外のとき session が既定の sessions テーブルに書かれ、
        // 管理画面リクエスト間で _token が不整合となり 419（CSRF mismatch）が発生する。
        $adminUrl = $this->resolveAdminUrl();
        if (str_starts_with($path, 'admin') || ($adminUrl !== '' && str_starts_with($path, $adminUrl))) {
            $this->currentGuard = 'member';

            return 'member';
        }

        // Mypage の場合は user ガード
        if (str_starts_with($path, 'mypage')) {
            $this->currentGuard = 'user';

            return 'user';
        }

        // その他のパスはデフォルトテーブル（sessions）を使用
        // 認証状態チェックは循環参照を引き起こすため削除
        $this->currentGuard = null;

        return null;
    }

    /**
     * 管理画面 URL を DB から解決する（プロセス内キャッシュ付き）
     *
     * SiteSetting::getValue を使うのが本筋だが、handler は早期ブート段階でも
     * 呼ばれ得るため Schema::hasTable で守る。DB 未接続時は config デフォルトに
     * フォールバック。空文字は「管理 URL 判定を無効化」ではなく「config 値を使用」とする。
     */
    protected function resolveAdminUrl(): string
    {
        if (self::$resolvedAdminUrl !== null) {
            return self::$resolvedAdminUrl;
        }

        $configDefault = config('admin.url.admin_url', 'admin') ?: 'admin';

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                $dbValue = \App\Models\SiteSetting::getValue('admin_url', $configDefault);
                if (is_string($dbValue) && $dbValue !== '') {
                    return self::$resolvedAdminUrl = $dbValue;
                }
            }
        } catch (\Throwable $e) {
            // DB 未接続 / インストール前等は config デフォルトへ
        }

        return self::$resolvedAdminUrl = $configDefault;
    }

    /**
     * セッションデータを読み込む
     *
     * @param  string  $sessionId
     * @return string|null
     */
    public function read($sessionId): string|false
    {
        $session = (object) $this->getQuery()
            ->where('id', $sessionId)
            ->first();

        if ($this->expired($session)) {
            $this->exists = true;

            return '';
        }

        if (isset($session->payload)) {
            $this->exists = true;

            return base64_decode($session->payload);
        }

        return '';
    }

    /**
     * クエリビルダーを取得（動的にテーブル名を設定）
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getQuery()
    {
        return $this->connection->table($this->getTable());
    }

    /**
     * セッションデータを書き込む
     *
     * @param  string  $sessionId
     * @param  string  $data
     */
    public function write($sessionId, $data): bool
    {
        $payload = $this->getDefaultPayload($data);

        if (! $this->exists) {
            $this->read($sessionId);
        }

        if ($this->exists) {
            $this->performUpdate($sessionId, $payload);
        } else {
            $this->performInsert($sessionId, $payload);
        }

        return $this->exists = true;
    }

    /**
     * デフォルトのペイロードを取得（テーブルに応じてカラム名を調整）
     *
     * @param  string  $data
     * @return array
     */
    protected function getDefaultPayload($data)
    {
        $payload = [
            'payload' => base64_encode($data),
            'last_activity' => $this->currentTime(),
        ];

        if (! $this->container) {
            return $payload;
        }

        $table = $this->getTable();
        $guard = $this->getCurrentGuard();

        // ゲスト用テーブルの場合（guardがnull）はuser_id/member_idを含めない
        if ($guard === null) {
            return array_merge($payload, [
                'ip_address' => $this->ipAddress(),
                'user_agent' => $this->userAgent(),
            ]);
        }

        // メンバー用テーブルの場合はmember_idを使用
        if ($guard === 'member') {
            return array_merge($payload, [
                'member_id' => $this->userId(),
                'ip_address' => $this->ipAddress(),
                'user_agent' => $this->userAgent(),
            ]);
        }

        // その他（ユーザープラグインなど）はuser_idを使用
        return array_merge($payload, [
            'user_id' => $this->userId(),
            'ip_address' => $this->ipAddress(),
            'user_agent' => $this->userAgent(),
        ]);
    }

    /**
     * セッションデータを更新
     *
     * @param  string  $sessionId
     * @param  array  $payload
     * @return int
     */
    protected function performUpdate($sessionId, $payload)
    {
        return $this->getQuery()
            ->where('id', $sessionId)
            ->update($payload);
    }

    /**
     * セッションデータを挿入
     *
     * @param  string  $sessionId
     * @param  array  $payload
     * @return bool
     */
    protected function performInsert($sessionId, $payload)
    {
        try {
            return $this->getQuery()->insert(array_merge(
                ['id' => $sessionId],
                $payload
            ));
        } catch (\Exception $e) {
            $this->performUpdate($sessionId, $payload);
        }
    }

    /**
     * テーブルに応じてペイロードをフィルタリング
     */
    protected function filterPayloadForTable(array $payload): array
    {
        $table = $this->getTable();

        // ゲスト用テーブル（sessions）の場合はuser_idを除外
        if ($table === 'sessions') {
            unset($payload['user_id']);
        }
        // メンバー用テーブル（members_sessions）の場合はuser_idをmember_idにリネーム
        elseif ($table === 'members_sessions' && isset($payload['user_id'])) {
            $payload['member_id'] = $payload['user_id'];
            unset($payload['user_id']);
        }

        return $payload;
    }

    /**
     * セッションを削除
     *
     * @param  string  $sessionId
     */
    public function destroy($sessionId): bool
    {
        $this->getQuery()->where('id', $sessionId)->delete();

        return true;
    }

    /**
     * 期限切れセッションをガベージコレクション
     *
     * @param  int  $lifetime
     */
    public function gc($lifetime): int
    {
        // 各ガードのテーブルをクリーンアップ
        $deleted = 0;

        foreach ($this->guardTables as $guard => $table) {
            $deleted += $this->connection->table($table)
                ->where('last_activity', '<=', $this->currentTime() - $lifetime)
                ->delete();
        }

        // デフォルトテーブルもクリーンアップ
        $deleted += $this->connection->table($this->table)
            ->where('last_activity', '<=', $this->currentTime() - $lifetime)
            ->delete();

        return $deleted;
    }
}
