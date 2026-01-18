/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

import '../scss/style.scss';
import './bootstrap';
import './appearance';
import '../../components/ui/js/tooltip';
import '../../components/form/js/color-picker';
import '../../components/form/js/email-input';
import '../../components/form/js/password-tools';
import '../../components/ui/js/notification';
import '../../components/ui/js/modal';
import '../../components/ui/js/passkey-device-name';
import '../../components/ui/js/passkey-result';
import '../../components/ui/js/recovery-codes';
import '../../components/ui/js/webauthn-utils';
import '../../components/ui/js/two-fa-management';
import '../../components/ui/js/pagination-controls';
import '../../components/ui/js/admin-bar';
import '../../components/ui/js/appearance-form';
import '../../components/mail/js/mail-server-test';
import '../../components/mail/js/mail-server-verification';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse'

Alpine.plugin(collapse)
window.Alpine = Alpine;
Alpine.start();
