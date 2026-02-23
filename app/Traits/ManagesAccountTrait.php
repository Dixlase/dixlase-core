<?php

namespace App\Traits;

use App\Services\MailServerValidatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * アカウント管理の共通処理
 * メンバーとユーザーの両方で使用可能
 */
trait ManagesAccountTrait
{
    /**
     * 認証メール送信処理（共通）
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  メンバーまたはユーザーモデル
     * @param  string  $successMessageKey  成功メッセージの翻訳キー
     * @param  string  $failedMessageKey  失敗メッセージの翻訳キー
     * @param  string  $redirectRouteName  リダイレクト先のルート名
     * @param  string  $mailServerNotTestedKey  メールサーバー未テストメッセージの翻訳キー
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendVerificationEmailToModel(
        $model,
        string $successMessageKey,
        string $failedMessageKey,
        string $redirectRouteName,
        string $mailServerNotTestedKey = 'admin/members/form.mail_server_not_tested'
    ) {
        try {
            if (! MailServerValidatorService::isMailServerTested()) {
                return response()->json([
                    'success' => false,
                    'message' => __($mailServerNotTestedKey),
                ], 400);
            }

            $model->email_verified_at = null;
            $model->save();

            // モデルの種類に応じて適切なセッションテーブルとカラムを使用
            $isMember = $model instanceof \App\Models\Member;
            $sessionTable = $isMember ? 'members_sessions' : 'users_sessions';
            $idColumn = $isMember ? 'member_id' : 'user_id';

            if (DB::getSchemaBuilder()->hasTable($sessionTable)) {
                DB::table($sessionTable)
                    ->where($idColumn, $model->id)
                    ->delete();
            }

            $model->sendEmailVerificationNotification('resend');

            $message = __($successMessageKey);
            $redirectUrl = route($redirectRouteName, [$this->getModelRouteParameterName() => $model->id]);

            session()->flash('success', $message);

            return response()->json([
                'success' => true,
                'redirect' => $redirectUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __($failedMessageKey),
            ], 500);
        }
    }

    /**
     * 強制ログアウト処理（共通）
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  メンバーまたはユーザーモデル
     * @param  string  $successMessageKey  成功メッセージの翻訳キー
     * @param  string  $redirectRouteName  リダイレクト先のルート名
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function forceLogoutModel(
        $model,
        string $successMessageKey,
        string $redirectRouteName
    ) {
        // モデルの種類に応じて適切なセッションテーブルとカラムを使用
        $isMember = $model instanceof \App\Models\Member;
        $sessionTable = $isMember ? 'members_sessions' : 'users_sessions';
        $idColumn = $isMember ? 'member_id' : 'user_id';

        if (DB::getSchemaBuilder()->hasTable($sessionTable)) {
            DB::table($sessionTable)
                ->where($idColumn, $model->id)
                ->delete();
        }

        return redirect()->route($redirectRouteName, [$this->getModelRouteParameterName() => $model->id])
            ->with('success', __($successMessageKey));
    }

    /**
     * Two-FAロックアウト解除処理（共通）
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  メンバーまたはユーザーモデル
     * @param  string  $twoFaAttemptModelClass  2FA試行モデルのクラス名
     * @param  string  $loginAttemptModelClass  ログイン試行モデルのクラス名
     * @param  string  $modelIdColumn  モデルIDカラム名（'member_id' または 'user_id'）
     * @param  string  $successMessageKey  成功メッセージの翻訳キー
     * @param  string  $redirectRouteName  リダイレクト先のルート名
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function unlockTwoFaForModel(
        $model,
        string $twoFaAttemptModelClass,
        string $loginAttemptModelClass,
        string $modelIdColumn,
        string $successMessageKey,
        string $redirectRouteName
    ) {
        $twoFaAttemptModelClass::where($modelIdColumn, $model->id)->delete();
        // 失敗した試行記録のみを削除（成功した記録は統計用に保持）
        $loginAttemptModelClass::where('identifier', $model->email)
            ->where('successful', false)
            ->delete();

        return redirect()->route($redirectRouteName, [$this->getModelRouteParameterName() => $model->id])
            ->with('success', __($successMessageKey));
    }

    /**
     * メール認証状態を処理（共通）
     *
     * @param  array  &$validated  バリデーション済みデータ（参照渡し）
     * @param  Request  $request  リクエスト
     * @param  \Illuminate\Database\Eloquent\Model  $model  モデル
     */
    protected function processEmailVerificationStatus(array &$validated, Request $request, $model): void
    {
        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        $emailVerified = (string) $request->input('email_verified', $isMailServerTested ? null : '1');
        $wasVerified = $model->hasVerifiedEmail();
        $emailChanged = $request->input('email') !== $model->email;

        if (! $isMailServerTested) {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '1') {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '0') {
            $validated['email_verified_at'] = null;
        } elseif ($emailChanged && $wasVerified) {
            $validated['email_verified_at'] = null;
        }

        unset($validated['email_verified']);
    }

    /**
     * メールアドレス変更時の認証メール送信判定と送信（共通）
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  モデル
     * @param  Request  $request  リクエスト
     * @param  bool  $wasVerified  変更前の認証状態
     * @param  string  $successMessageKey  成功メッセージの翻訳キー
     * @param  string  $failedMessageKey  失敗メッセージの翻訳キー
     * @param  string  $defaultMessageKey  デフォルトメッセージの翻訳キー
     * @param  string  $context  通知コンテキスト（'email_change'など）
     * @return string メッセージの翻訳キー
     */
    protected function sendEmailVerificationIfNeeded(
        $model,
        Request $request,
        bool $wasVerified,
        string $successMessageKey,
        string $failedMessageKey,
        string $defaultMessageKey,
        string $context = 'email_change'
    ): string {
        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        $emailChanged = $request->input('email') !== $model->getOriginal('email');
        $shouldSendEmail = $isMailServerTested && $emailChanged && $wasVerified;

        if ($shouldSendEmail && ! $model->hasVerifiedEmail()) {
            try {
                $model->sendEmailVerificationNotification($context);

                return $successMessageKey;
            } catch (\Exception $e) {
                Log::error('Failed to send verification email', [
                    'model_id' => $model->id,
                    'error' => $e->getMessage(),
                ]);

                return $failedMessageKey;
            }
        }

        return $defaultMessageKey;
    }

    /**
     * モデルのルートパラメータ名を取得
     * 継承先で実装する
     */
    abstract protected function getModelRouteParameterName(): string;
}
