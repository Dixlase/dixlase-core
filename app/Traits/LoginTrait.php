<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * ログイン・認証処理の共通トレイト
 * 
 * ログイン処理と二段階認証処理で共通して使用される設定メソッドと機能を提供します。
 * このトレイトを使用するコントローラーは、以下の抽象メソッドを実装する必要があります。
 */
trait LoginTrait
{
    /**
     * ログイン画面のルート名を取得（継承先で実装）
     * 
     * @return string ルート名（例: 'admin.login', 'users-plugin::mypage.login'）
     */
    abstract protected function getLoginRoute(): string;

    /**
     * ダッシュボードのルート名を取得（継承先で実装）
     * 
     * @return string ルート名（例: 'admin.dashboard', 'users-plugin::mypage.dashboard'）
     */
    abstract protected function getDashboardRoute(): string;

    /**
     * セッションキーのプレフィックスを取得（継承先で実装）
     * 
     * @return string プレフィックス（例: 'login', 'two_fa'）
     */
    abstract protected function getSessionPrefix(): string;

    /**
     * ユーザーモデルクラス名を取得（継承先で実装）
     * 
     * @return string モデルクラス名（例: 'App\Models\Member', 'Plugins\DixlaseUsers\App\Models\DixlaseUsersUser'）
     */
    abstract protected function getUserModelClass(): string;

    /**
     * 認証ガード名を取得（継承先で実装）
     * 
     * @return string ガード名（例: 'member', 'user'）
     */
    abstract protected function getGuardName(): string;

    /**
     * コンテキストを取得（継承先で実装）
     * 
     * @return string コンテキスト（'admin' または 'user'）
     */
    abstract protected function getContext(): string;

    /**
     * 二段階認証ルートのプレフィックスを取得
     * 
     * @return string ルートプレフィックス（例: 'admin', 'users-plugin::mypage'）
     */
    abstract protected function getTwoFaRoutePrefix(): string;
}
