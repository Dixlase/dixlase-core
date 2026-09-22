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

namespace App\Services\TwoFa\Passkeys;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use LogicException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * StorePasskey that checks credential uniqueness in the owner's own table.
 *
 * The package checks uniqueness against the single, globally configured
 * passkey model. Dixlase keeps admin-member passkeys and plugin-user
 * passkeys in separate tables, so the check has to run against the table
 * behind the owner's passkeys() relation instead.
 *
 * @internal
 */
class StoreOwnedPasskey extends StorePasskey
{
    private ?PasskeyUser $owner = null;

    public function __invoke(
        Authenticatable $user,
        string $name,
        PublicKeyCredential $credential,
        PublicKeyCredentialCreationOptions $options
    ): Passkey {
        if (! $user instanceof PasskeyUser) {
            throw new LogicException('Passkey owners must implement '.PasskeyUser::class.'.');
        }

        $this->owner = $user;

        try {
            return parent::__invoke($user, $name, $credential, $options);
        } finally {
            $this->owner = null;
        }
    }

    /**
     * @throws InvalidPasskeyException
     */
    protected function ensureCredentialIsUnique(CredentialRecord $source): void
    {
        if (! $this->owner instanceof PasskeyUser) {
            throw new LogicException('StoreOwnedPasskey must be invoked with an owner.');
        }

        $credentialId = Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId);

        $exists = $this->owner->passkeys()->getRelated()->newQuery()
            ->where('credential_id', $credentialId)
            ->exists();

        if ($exists) {
            throw InvalidPasskeyException::make('Unable to register this passkey.');
        }
    }
}
