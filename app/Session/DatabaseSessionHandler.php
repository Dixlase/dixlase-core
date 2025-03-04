<?php

namespace App\Session;


use Illuminate\Database\ConnectionInterface as Connection;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Session\DatabaseSessionHandler as BaseDatabaseSessionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Database\QueryException;


class DatabaseSessionHandler extends BaseDatabaseSessionHandler
{
    public function __construct(
        Connection $connection,
        string $table,
        int|string $minutes,
        Application $container = null
    ) {
        parent::__construct($connection, $table, $minutes, $container);
    }


    /**
     * セッション書き込み時に適切なテーブルへ移動
     */
    protected function performInsert($sessionId, $payload)
    {
        // テーブルを動的に選択
        $chosenTable = $this->chooseTable();

        try {
            return $this->getQuery($chosenTable)
                ->insert(Arr::set($payload, 'id', $sessionId));
        } catch (QueryException) {
            $this->performUpdate($sessionId, $payload);
        }
    }

    /**
     * Perform an update operation on the session ID.
     *
     * @param  string  $sessionId
     * @param  array<string, mixed>  $payload
     * @return int
     */
    protected function performUpdate($sessionId, $payload)
    {
        $chosenTable = $this->chooseTable();

        return $this->getQuery($chosenTable)
            ->where('id', $sessionId)
            ->update($payload);
    }

    /**
     * Get the default payload for the session.
     *
     * @param  string  $data
     * @return array
     */
    protected function getDefaultPayload($data)
    {
        // 親メソッドとほぼ同じ。user_id や ip_address, user_agent を追加する処理は任意
        $payload = [
            'payload' => base64_encode($data),
            'last_activity' => $this->currentTime(),
        ];

        if (! $this->container) {
            return $payload;
        }

        return tap($payload, function (&$payload) {
            $this->addUserInformation($payload)
                ->addRequestInformation($payload);
        });
    }

    /**
     * Laravelのデフォルトでは user_id にユーザーIDを保存するが、
     * member_id を使いたいのでオーバーライド。
     */
    protected function addUserInformation(&$payload)
    {
        // もし "member" ガードがログインしていれば member_id に保存
        if (Auth::guard('member')->check()) {
            $payload['member_id'] = Auth::guard('member')->id();
        }

        return $this; // 親クラスはさらに user_id を入れていたが、不要なら入れない
    }




    /**
     * ログイン状況で適切なテーブルを選択
     */
    /**
     * Choose which table to use: 'sessions' or 'members_sessions'.
     */
    protected function chooseTable(): string
    {
        // “ログインしているか” の判定
        // ここでは "member" ガードを想定
        $isMemberLoggedIn = Auth::guard('member')->check();

        if ($isMemberLoggedIn) {
            return 'members_sessions';
        }

        // 未ログインの場合は "sessions" を使う
        return 'sessions';
    }

    /**
     * Overwrite parent getQuery() to pass chosen table dynamically.
     */
    protected function getQuery($tableName = null)
    {
        $tableName = $tableName ?? $this->table; // fallback to default
        return $this->connection->table($tableName);
    }

    /**
     * Overwrite parent's destroy() if needed (optional)
     * e.g. searching both tables to destroy a session
     */
    public function destroy($sessionId): bool
    {
        // 例: members_sessions からまず消す
        $this->getQuery('members_sessions')->where('id', $sessionId)->delete();
        // 例: sessions からも消す
        $this->getQuery('sessions')->where('id', $sessionId)->delete();

        return true;
    }




    /*
    public function write($sessionId, $data): bool
    {
        $payload = [
            'id'            => $sessionId,
            'payload'       => base64_encode($data),
            'last_activity' => $this->currentTime(),
            'ip_address'    => request()->ip() ?? null,
            'user_agent'    => substr(request()->userAgent() ?? '', 0, 500),
        ];

        if ($this->container->bound('auth')) {
            $auth = $this->container->make('auth');
            // 管理者ログイン時の処理
            if ($auth->guard('member')->check()) {
                $payload['member_id'] = $auth->guard('member')->id();
                return $this->performSessionWrite($sessionId, 'members_sessions', $payload);
            }
            // 一般ユーザーログイン時の処理（User モデルが存在する場合のみ）
            elseif ($this->isUserGuardAvailable()) {
                $payload['user_id'] = $auth->guard('web')->id();
                return $this->performSessionWrite($sessionId, 'users_sessions', $payload);
            }
        }

        // 未ログイン時は `sessions` にそのまま保存
        // `sessions` テーブルには `user_id` を含めない
        unset($payload['user_id']);
        return $this->performSessionWrite($sessionId, 'sessions', $payload);
    }
        */

    /**
     * 未ログイン時の `sessions` のデータを移行する
     */

    /*
    private function migrateSession($sessionId, $fromTable, $toTable, $payload)
    {
        $session = DB::table($fromTable)->where('id', $sessionId)->first();

        if ($session) {
            DB::table($toTable)->insert($payload);
            DB::table($fromTable)->where('id', $sessionId)->delete();
        }
    }
        */

    /**
     * セッション情報を `sessions` に書き込む
     */

    /*
    private function performSessionWrite($sessionId, $table, $payload): bool
    {

        if ($table === 'sessions') {
            unset($payload['user_id']); // sessions には user_id を含めない
        }

        try {
            DB::table($table)->updateOrInsert(['id' => $sessionId], $payload);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
        */


    /**
     * `web` ガードが利用可能かチェック（User モデルがコアに含まれない場合の回避策）
     */
    /*
    private function isUserGuardAvailable(): bool
    {
        $authConfig = config('auth.providers.users.model') ?? null;
        return !empty($authConfig) && class_exists($authConfig);
    }
    */
}
