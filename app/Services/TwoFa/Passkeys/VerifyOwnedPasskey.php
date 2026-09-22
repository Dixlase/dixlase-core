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

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use LogicException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * VerifyPasskey bound to a known owner.
 *
 * Dixlase never runs a username-less (discoverable) login: every
 * verification already knows which member or user is signing in. This
 * subclass makes that owner mandatory and resolves the passkey through the
 * owner's passkeys() relation, so:
 *
 * - the lookup hits the owner's own table (admin members and plugin users
 *   keep passkeys in separate tables), and
 * - ownership is checked on the relation's actual foreign key and model
 *   class, not only on the raw ID value the package compares against
 *   `user_id`.
 *
 * @internal
 */
class VerifyOwnedPasskey extends VerifyPasskey
{
    private ?PasskeyUser $owner = null;

    /**
     * @throws InvalidPasskeyException
     */
    public function __invoke(
        PublicKeyCredential $credential,
        PublicKeyCredentialRequestOptions $options,
        ?PasskeyUser $user = null
    ): Passkey {
        if (! $user instanceof PasskeyUser) {
            throw new LogicException('Passkey verification in Dixlase always requires the expected owner.');
        }

        $this->owner = $user;

        try {
            return parent::__invoke($credential, $options, $user);
        } finally {
            $this->owner = null;
        }
    }

    /**
     * @throws InvalidPasskeyException
     */
    public function getPasskey(PublicKeyCredential $credential, bool $lock = false): Passkey
    {
        $relation = $this->ownerRelation();
        $credentialId = Base64UrlSafe::encodeUnpadded($credential->rawId);

        $query = $relation->getRelated()->newQuery()->where('credential_id', $credentialId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $passkey = $query->first();

        if (! $passkey instanceof Passkey) {
            throw InvalidPasskeyException::make('Passkey not recognized. It may have been removed from your account.');
        }

        return $passkey;
    }

    /**
     * @throws InvalidPasskeyException
     */
    public function ensurePasskeyBelongsToUser(Passkey $passkey, ?PasskeyUser $user): void
    {
        if (! $user instanceof PasskeyUser) {
            throw new LogicException('Passkey verification in Dixlase always requires the expected owner.');
        }

        $relation = $user->passkeys();
        $ownerModel = $relation->getRelated();
        $ownerKey = $user->getKey();

        $belongs = $passkey instanceof $ownerModel
            && is_scalar($ownerKey)
            && (string) $passkey->getAttribute($relation->getForeignKeyName()) === (string) $ownerKey;

        if (! $belongs) {
            throw InvalidPasskeyException::make('Passkey not recognized. It may have been removed from your account.');
        }
    }

    /**
     * @return HasMany<Passkey, \Illuminate\Database\Eloquent\Model>
     */
    private function ownerRelation(): HasMany
    {
        if (! $this->owner instanceof PasskeyUser) {
            throw new LogicException('VerifyOwnedPasskey must be invoked with an owner.');
        }

        return $this->owner->passkeys();
    }
}
