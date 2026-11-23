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

namespace App\Contracts\Install;

/**
 * Bootstraps a freshly-provisioned install into a clean, healthy state.
 *
 * A plugin that provisions copies of an already-installed instance (for
 * example, per-request demo sandboxes) needs each copy to start from the
 * same clean state the installer leaves behind, without reaching past the
 * `@api` boundary into core's audit and integrity tables. The shape of
 * those tables is core's own business and changes over time; this contract
 * lets a provisioner ask core to do the work so the knowledge stays in one
 * place.
 *
 * Every method operates on the **default** database connection. A caller
 * that provisions a separate install points the default connection (and,
 * for the baseline, the storage path) at that install for the duration of
 * the call and restores it afterwards — the same arrangement core
 * anticipates for DB-per-site through {@see \App\Contracts\Site\SiteContextInterface::connection()}.
 */
interface FreshInstallBootstrapperInterface
{
    /**
     * Empty the audit trail to the clean state core reports as healthy.
     *
     * A copied install inherits audit rows, daily seals, retention points
     * and key epochs from the instance it was copied from. Those rows can
     * never verify on the copy — the hash chain and seals are keyed to the
     * origin's secret — so the only portable healthy state is an empty
     * trail, which {@see \App\Services\AuditLogIntegrityService::getHealth()}
     * reports as `HEALTH_EMPTY`. Core owns the full set of audit-trail
     * tables, so callers need not track it as the subsystem grows.
     *
     * The chain lock is reset rather than removed, so the next audit entry
     * starts a fresh chain exactly as it would on a brand-new install.
     */
    public function resetAuditTrail(): void;

    /**
     * Record a clean core file-integrity baseline audit row.
     *
     * A copy shares the origin's core tree, so its files already match the
     * on-disk baseline and no scan is needed — but Site Health keeps
     * recommending a baseline until a `scope=core` audit row exists. This
     * records that row from the current on-disk baseline file (no rehash),
     * mirroring what the installer records at install time.
     *
     * When no baseline file is present, nothing is recorded: the method
     * reflects an existing baseline rather than creating one.
     */
    public function recordCoreBaselineAudit(): void;
}
