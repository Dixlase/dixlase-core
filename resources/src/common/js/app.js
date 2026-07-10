/*
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

import '../scss/style.scss';
import './bootstrap';
import './appearance';
// Dry-run release marker — remove after the current dry-run cycle
// completes. See dryrun-marker.js for the sandbox verification recipe.
import './dryrun-marker.js';
import './livewire-notification';
import '../../components/auth/js/account-verification';
import '../../components/js/form-color';
import '../../components/js/form-email';
import '../../components/js/form-password-tools';
import '../../components/js/ui-notification';
import '../../components/js/ui-modal';
import '../../components/js/csrf-error-handler';
import '../../components/js/ui-tooltip';
import '../../components/js/ui-pagination-controls';
import '../../components/js/ui-admin-bar';
import '../../components/js/ui-appearance-mode-selector';
import '../../components/two-fa/js/passkey-device-name';
import '../../components/two-fa/js/email-challenge';
import '../../components/two-fa/js/passkey-challenge';
import '../../components/two-fa/js/recovery-code-challenge';
import '../../components/two-fa/js/passkey-result';
import '../../components/two-fa/js/recovery-codes';
import '../../components/two-fa/js/passkey-prompt';
import '../../admin/two-fa/js/passkey-prompt-modal';
import '../../components/two-fa/js/two-fa-profile-settings';
import '../../components/two-fa/js/webauthn-utils';
import '../../components/two-fa/js/two-fa-management';
import '../../components/js/form-content-editor';
import '../../components/js/content-preview-mixin';
import '../../components/media/js/selector';
import '../../components/mail-server/js/test';
import '../../components/mail-server/js/verification';
import '../../admin/js/layout';
import '../../admin/settings/security/js/csp';
import '../../admin/settings/base/js/maintenance';
import '../../admin/settings/base/js/mode';
import '../../admin/settings/security/js/captcha';
import '../../admin/settings/systems/js/api';
import '../../admin/settings/plugins/js/audit';
import '../../admin/settings/plugins/js/two-stage-modal';
import '../../admin/settings/plugins/js/online-plugins';
import '../../admin/settings/themes/js/audit';
import '../../admin/settings/themes/js/online-themes';
import '../../admin/js/login-flow';
import '../../admin/js/getting-started-card';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse'

Alpine.plugin(collapse)
window.Alpine = Alpine;
Alpine.start();