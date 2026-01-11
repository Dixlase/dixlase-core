<?php

namespace App\Services;

use App\Services\TwoFa\TwoFaService;
use App\Helpers\TwoFaHelper;
use App\Services\EmailAuthenticationService;
use App\Services\TwoFa\TwoFaAttemptService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Models\MemberSetting;

/**
 * 管理画面用の二段階認証サービス（ラッパークラス）
 * 
 * 後方互換性のために残されています。
 * 実際の処理はTwoFaServiceに委譲されます。
 * 設定モデルとしてMemberSetting::classを使用し、コンテキストは'admin'です。
 */
class AdminTwoFaService extends TwoFaService
{
    public function __construct(
        TwoFaHelper $helper,
        EmailAuthenticationService $emailAuth,
        TwoFaPasskeyService $passkeyAuth,
        TwoFaRecoveryCodeService $recoveryCode,
        TwoFaAttemptService $attemptService
    ) {
        parent::__construct(
            $helper,
            $emailAuth,
            $passkeyAuth,
            $recoveryCode,
            $attemptService,
            MemberSetting::class,
            'admin'
        );
    }
}
