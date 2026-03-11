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

        // 管理画面の場合はmemberガード
        if (str_starts_with($path, 'admin')) {
            $this->currentGuard = 'member';

            return 'member';
        }

        // Mypageの場合はuserガード
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

        // 他のガードテーブルにもセッションを同期
        // これにより、異なるガード間でセッションが共有される
        $this->syncToOtherTables($sessionId, $data);

        return $this->exists = true;
    }

    /**
     * 他のガードテーブルにセッションを同期
     *
     * @param  string  $sessionId
     * @param  string  $data
     */
    protected function syncToOtherTables($sessionId, $data): void
    {
        $currentTable = $this->getTable();
        $basePayload = [
            'payload' => base64_encode($data),
            'last_activity' => $this->currentTime(),
            'ip_address' => $this->ipAddress(),
            'user_agent' => $this->userAgent(),
        ];

        // 各ガードテーブルに同期
        foreach ($this->guardTables as $guard => $table) {
            if ($table === $currentTable) {
                continue;
            }

            // テーブルに応じたペイロードを作成
            $payload = $basePayload;
            if ($guard === 'member') {
                $payload['member_id'] = null; // 他ガードからの同期なのでnull
            } else {
                $payload['user_id'] = null; // 他ガードからの同期なのでnull
            }

            $this->syncToTable($table, $sessionId, $payload);
        }
    }

    /**
     * 指定テーブルにセッションを同期
     *
     * @param  string  $table
     * @param  string  $sessionId
     * @param  array  $payload
     */
    protected function syncToTable($table, $sessionId, $payload): void
    {
        try {
            $exists = $this->connection->table($table)
                ->where('id', $sessionId)
                ->exists();

            if ($exists) {
                $this->connection->table($table)
                    ->where('id', $sessionId)
                    ->update($payload);
            } else {
                $this->connection->table($table)
                    ->insert(array_merge(['id' => $sessionId], $payload));
            }
        } catch (\Exception $e) {
            // 同期エラーは無視（メインテーブルへの書き込みは成功している）
            \Log::warning('[Session] Failed to sync to table', [
                'table' => $table,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
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
