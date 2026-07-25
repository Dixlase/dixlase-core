<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

namespace App\Services\TwoFa;

use App\Contracts\TwoFaInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TwoFaRecoveryCodeService
{
    protected string $settingModelClass;

    public function __construct(string $settingModelClass = \App\Models\SecuritySetting::class)
    {
        $this->settingModelClass = $settingModelClass;
    }

    /**
     * Generate recovery codes
     *
     * @return array Array of generated recovery codes (plaintext)
     */
    public function generate(TwoFaInterface $user): array
    {
        // Get number to generate from settings (1-5 codes, default 5)
        $count = (int) $this->settingModelClass::getValue('two_fa_recovery_codes_count', 5);
        $count = max(1, min(5, $count)); // Limit to range of 1-5

        // Invalidate all existing recovery codes
        $user->twoFaRecoveryCodes()->update(['disabled' => true]);

        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Generate 20-digit number (5 digits × 4 blocks)
            $code = $this->generateCode();
            $codes[] = $code;

            // Hash and save
            $user->twoFaRecoveryCodes()->create([
                'code' => Hash::make($code),
                'disabled' => false,
            ]);
        }

        Log::info('[Recovery Code] Generated new codes', [
            'user_id' => $user->getId(),
            'count' => $count,
        ]);

        return $codes;
    }

    /**
     * Generate 20-digit recovery code (5 digits × 4 blocks)
     */
    protected function generateCode(): string
    {
        $blocks = [];
        for ($i = 0; $i < 4; $i++) {
            $blocks[] = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        }

        return implode('', $blocks);
    }

    /**
     * Verify recovery code
     */
    public function validate(TwoFaInterface $user, string $code): bool
    {
        // Remove hyphens and spaces
        $code = preg_replace('/[\s\-]/', '', $code);

        // Get valid recovery codes
        $recoveryCodes = $user->twoFaRecoveryCodes()
            ->where('disabled', false)
            ->whereNull('used_at')
            ->get();

        foreach ($recoveryCodes as $recoveryCode) {
            if (Hash::check($code, $recoveryCode->code)) {
                // Mark as used
                $recoveryCode->markAsUsed();

                Log::info('[Recovery Code] Code used successfully', [
                    'user_id' => $user->getId(),
                    'recovery_code_id' => $recoveryCode->id,
                ]);

                return true;
            }
        }

        Log::warning('[Recovery Code] Invalid code attempt', [
            'user_id' => $user->getId(),
        ]);

        return false;
    }

    /**
     * Get number of remaining valid recovery codes
     */
    public function getRemainingCount(TwoFaInterface $user): int
    {
        return $user->twoFaRecoveryCodes()
            ->where('disabled', false)
            ->whereNull('used_at')
            ->count();
    }

    /**
     * Check if recovery codes can be regenerated
     */
    public function canRegenerate(TwoFaInterface $user): bool
    {
        // Get last generation date and time
        $lastGenerated = $user->twoFaRecoveryCodes()
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastGenerated) {
            return true; // Can generate if not yet generated
        }

        // Check if 24 hours have elapsed
        $interval = (int) $this->settingModelClass::getValue('two_fa_recovery_code_regenerate_interval', 24);
        $canRegenerateAt = $lastGenerated->created_at->addHours($interval);

        return Carbon::now()->greaterThanOrEqualTo($canRegenerateAt);
    }

    /**
     * Get next available regeneration date and time
     */
    public function getNextRegenerateTime(TwoFaInterface $user): ?Carbon
    {
        $lastGenerated = $user->twoFaRecoveryCodes()
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastGenerated) {
            return null;
        }

        $interval = (int) $this->settingModelClass::getValue('two_fa_recovery_code_regenerate_interval', 24);

        return $lastGenerated->created_at->addHours($interval);
    }

    /**
     * Check if recovery codes exist
     */
    public function hasRecoveryCodes(TwoFaInterface $user): bool
    {
        return $user->twoFaRecoveryCodes()
            ->where('disabled', false)
            ->exists();
    }

    /**
     * Format recovery code (for display)
     * Example: 12345-67890-12345-67890
     */
    public function formatCode(string $code): string
    {
        // Split 20 digits into 5-digit segments
        $blocks = str_split($code, 5);

        return implode('-', $blocks);
    }

    /**
     * Delete (invalidate) all recovery codes
     *
     * @return int Number of deleted recovery codes
     */
    public function revokeAll(TwoFaInterface $user): int
    {
        $count = $user->twoFaRecoveryCodes()
            ->where('disabled', false)
            ->count();

        $user->twoFaRecoveryCodes()
            ->update(['disabled' => true]);

        Log::info('[Recovery Code] All codes revoked', [
            'user_id' => $user->getId(),
            'count' => $count,
        ]);

        return $count;
    }
}
