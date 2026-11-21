/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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

import Alpine from 'alpinejs';

/**
 * The delete section of the member privacy screen.
 *
 * The submit button stays disabled until the confirmation box is ticked,
 * and submitting asks once more with the prompt in the form's
 * data-confirm-prompt. The prompt is read from the attribute so that a
 * translation is never part of a directive expression.
 */
Alpine.data('privacyUserDelete', () => ({
    confirmed: false,

    confirmSubmit(event) {
        if (!window.confirm(event.currentTarget.dataset.confirmPrompt)) {
            event.preventDefault();
        }
    },
}));
