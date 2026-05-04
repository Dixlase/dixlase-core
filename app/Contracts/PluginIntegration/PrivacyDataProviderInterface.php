<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

declare(strict_types=1);

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\PluginPrivacy\UserDataDeletionDTO;
use App\DTO\PluginPrivacy\UserDataExportDTO;
use App\Enums\PluginPrivacy\DeletionMode;

/**
 * Contract that a plugin (or core subsystem) implements to declare which
 * personal data it stores about a given user, and how that data should
 * be exported or erased on request.
 *
 * This contract is the data-portability counterpart to the existing
 * PrivacyPolicyProviderInterface, which only deals with displaying a
 * plain-text privacy policy URL. Implementations of this contract are
 * the engine behind GDPR / personal-information-protection workflows
 * such as "subject access request" exports and "right to be forgotten"
 * deletions.
 *
 * Registration:
 *   $this->app->tag(
 *       [MyPrivacyDataProvider::class],
 *       \App\Services\Plugin\PluginServiceResolver::CAPABILITY_TAG,
 *   );
 *
 * Resolution:
 *   $resolver = app(\App\Services\Plugin\PluginServiceResolver::class);
 *   $results = $resolver->resolveAll(PrivacyDataProviderInterface::class);
 *
 * Multisite scoping:
 *   - $siteId === null  : network-wide (all sites + globally-scoped tables)
 *   - $siteId !== null  : restrict to data tied to that site only.
 *                         Providers whose tables are not site-scoped
 *                         (e.g. members, sessions) MUST return an empty
 *                         result with an explanatory entry in
 *                         UserDataExportDTO::$warnings.
 *
 * Implementations MUST NOT throw for "no data" cases; return an empty
 * DTO instead. Reserve exceptions for unrecoverable infrastructure
 * failures so the aggregator can continue running other providers.
 */
interface PrivacyDataProviderInterface extends PluginCapabilityInterface
{
    /**
     * Stable identifier used as the root directory inside the export
     * ZIP archive and as the key in admin UI tables.
     *
     * Typically the plugin slug (e.g. 'dixlase-users'). For core
     * providers, prefix with 'core-' (e.g. 'core-members') to avoid
     * collisions with plugins.
     */
    public function privacyProviderKey(): string;

    /**
     * Human-readable description of the data this provider handles,
     * keyed by locale. Shown to operators in the admin UI so they
     * understand what the export ZIP will contain before they run it.
     *
     * Example:
     *   ['en' => 'Stores user inquiries and replies.',
     *    'ja' => 'Stores user inquiries and replies.']
     *
     * @return array<string, string>
     */
    public function privacyDataDescription(): array;

    /**
     * Export every record this provider holds about the given user.
     *
     * Implementations should populate UserDataExportDTO::$data with a
     * plain associative array (one key per logical record group) so it
     * round-trips through json_encode/json_decode cleanly. Binary
     * artefacts (avatars, attachments) belong in
     * UserDataExportDTO::$files keyed by the path the file should
     * occupy inside the ZIP.
     *
     * @param  int  $userId  Subject identifier. For core providers this is
     *                       members.id; plugin providers may use their own
     *                       primary key, provided the admin UI knows how to
     *                       pass it.
     * @param  int|null  $siteId  null = network-wide; int = data tied to
     *                            that site only.
     */
    public function exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO;

    /**
     * Delete or anonymize every record this provider holds about the
     * given user.
     *
     * The provider is responsible for honoring foreign-key ordering
     * within its own tables. The aggregator does not coordinate
     * deletion order across providers and assumes each provider's
     * deletion is internally consistent.
     *
     * For DeletionMode::Anonymize, providers MUST hash identifying
     * fields with hash_hmac('sha256', $value, $salt) where $salt is
     * derived from app.key (see docs/plugin-development/privacy.md
     * for the canonical derivation).
     *
     * @param  int  $userId  Subject identifier (see exportUserData).
     * @param  DeletionMode  $mode  Strategy chosen by the operator.
     * @param  int|null  $siteId  null = network-wide; int = site-scoped.
     */
    public function deleteUserData(
        int $userId,
        DeletionMode $mode,
        ?int $siteId = null,
    ): UserDataDeletionDTO;
}
