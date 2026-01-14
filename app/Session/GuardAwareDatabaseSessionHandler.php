<?php

namespace App\Session;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\Auth;
use Log;

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
     *
     * @param string $guard
     * @param string $table
     * @return void
     */
    public function setGuardTable(string $guard, string $table): void
    {
        $this->guardTables[$guard] = $table;
    }

    /**
     * ガード判定ロジックを登録
     *
     * @param callable $resolver
     * @return void
     */
    public function addGuardResolver(callable $resolver): void
    {
        $this->guardResolvers[] = $resolver;
    }

    /**
     * 現在のガードに基づいてテーブル名を取得
     *
     * @return string
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
     *
     * @return string|null
     */
    protected function getCurrentGuard(): ?string
    {
        // すでに設定されている場合はそれを返す
        if ($this->currentGuard) {
            return $this->currentGuard;
        }

        $path = request()->path();
        \Log::info('GuardAwareDatabaseSessionHandler: getCurrentGuard called', [
            'path' => $path,
            'resolver_count' => count($this->guardResolvers),
        ]);

        // カスタムリゾルバーを優先的に実行
        foreach ($this->guardResolvers as $index => $resolver) {
            $guard = $resolver(request());
            \Log::info('GuardAwareDatabaseSessionHandler: Resolver executed', [
                'resolver_index' => $index,
                'returned_guard' => $guard,
            ]);
            if ($guard !== null) {
                \Log::info('GuardAwareDatabaseSessionHandler: Guard selected by resolver', [
                    'guard' => $guard,
                ]);
                return $guard;
            }
        }

        // デフォルトのガード判定（管理画面のみ）
        
        // 管理画面の場合はmemberガード
        if (str_starts_with($path, 'admin')) {
            \Log::info('GuardAwareDatabaseSessionHandler: Guard selected by default (admin)', [
                'guard' => 'member',
            ]);
            return 'member';
        }

        // 認証済みのガードを確認
        foreach (array_keys(config('auth.guards', [])) as $guard) {
            if (Auth::guard($guard)->check()) {
                \Log::info('GuardAwareDatabaseSessionHandler: Guard selected by auth check', [
                    'guard' => $guard,
                ]);
                return $guard;
            }
        }

        \Log::info('GuardAwareDatabaseSessionHandler: No guard found');
        return null;
    }

    /**
     * セッションデータを読み込む
     *
     * @param string $sessionId
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
     * @param string $sessionId
     * @param string $data
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        $guard = $this->getCurrentGuard();
        $table = $this->getTable();
        
        Log::info('GuardAwareDatabaseSessionHandler: write called', [
            'session_id' => $sessionId,
            'guard' => $guard,
            'table' => $table,
            'exists' => $this->exists,
        ]);
        
        $payload = $this->getDefaultPayload($data);

        if (!$this->exists) {
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
     * セッションデータを更新
     *
     * @param string $sessionId
     * @param array $payload
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
     * @param string $sessionId
     * @param array $payload
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
     * セッションを削除
     *
     * @param string $sessionId
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $this->getQuery()->where('id', $sessionId)->delete();

        return true;
    }

    /**
     * 期限切れセッションをガベージコレクション
     *
     * @param int $lifetime
     * @return int
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
