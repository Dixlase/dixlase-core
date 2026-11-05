/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * Registers the shared `accordion` data for the setup wizard, where the
 * install hint component (components/install/hint.blade.php) folds its
 * explanation into an animated panel (x-collapse).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

import Alpine from 'alpinejs';
import { accordion } from '../../common/js/alpine-data/index.js';

Alpine.data('accordion', accordion);
