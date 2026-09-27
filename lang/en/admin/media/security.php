<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

return [
    'size_exceeded' => ':category file size exceeds limit (:size / max :max)',
    'category' => [
        'image' => 'Image',
        'video' => 'Video',
        'document' => 'Document',
        'archive' => 'Archive',
        'other' => 'Other',
    ],
    'svg' => [
        'read_error' => 'Failed to read SVG file',
        'will_sanitize' => 'SVG file contains dangerous elements and will be sanitized',
        'unsafe' => 'SVG file contains dangerous elements. Upload rejected because sanitization is disabled.',
    ],
    'zip' => [
        'file_not_found' => 'ZIP file not found',
        'invalid_zip' => 'Invalid ZIP file',
        'too_many_files' => 'Too many files in ZIP (:count / max :max)',
        'path_traversal' => 'ZIP file contains dangerous path: :file',
        'forbidden_extension' => 'ZIP file contains forbidden extension: :file (:extension)',
        'hidden_file' => 'ZIP file contains hidden file: :file',
        'size_exceeded' => 'Uncompressed ZIP size exceeds limit (:size / max :max)',
        'compression_bomb' => 'Possible ZIP bomb detected. Compression ratio too high (:ratio times / max :max times)',
    ],
    'mime' => [
        'unknown_extension' => 'Unknown extension: :extension',
        'mime_mismatch' => 'File content does not match extension (:extension: expected :expected, detected :detected)',
        'invalid_image' => 'Image file is corrupted or invalid format',
        'invalid_svg' => 'SVG file is invalid format',
        'magic_bytes_mismatch' => 'File magic bytes do not match',
    ],
];
