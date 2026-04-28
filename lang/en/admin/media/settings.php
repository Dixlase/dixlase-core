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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

return [
    'heading' => 'Media Settings',
    'description' => 'Configure allowed file types and maximum file size for uploads.',
    'allowed_file_types' => 'Allowed File Types',
    'max_file_size' => 'Maximum File Size',
    'file_size_range' => '(1MB - 100MB)',
    'save_confirmation_title' => 'Media Settings Save Confirmation',
    'save_confirmation_message' => 'Do you want to save the media settings?',
    'svg_warning' => 'SVG files have security risks',
    'zip_warning' => 'ZIP files have security risks',
    'pdf_warning' => 'PDF files may contain macros',
    'docx_warning' => 'Word files may contain macros',
    'tex_warning' => 'TeX files may execute external commands',
    'zip_security_note' => 'ZIP files are subject to security checks',
    'risky_types_warning_title' => 'File types with security risks are enabled',
    'risky_types_warning_description' => 'The following file types have security risks. Ensure only trusted users can upload files.',
    'risk' => [
        'svg' => 'May contain JavaScript or external references, which could be exploited for XSS attacks.',
        'zip' => 'May contain ZIP bombs or malware. Could consume server resources when extracted.',
        'pdf' => 'May contain JavaScript or macros that could affect users who download the file.',
        'docx' => 'May contain VBA macros that could affect users who download the file.',
        'tex' => 'Commands like \\input or \\write18 may read external files or execute shell commands.',
    ],
    'file_size_limits' => 'File Size Limits by Type',
    'file_size_limits_description' => 'Set maximum upload size for each file type.',
    'category' => [
        'image' => 'Image',
        'video' => 'Video',
        'document' => 'Document',
        'archive' => 'Archive',
    ],
    'security' => 'Security Settings',
    'mime_validation' => 'MIME Content Validation',
    'mime_validation_description' => 'Validates file content and detects extension spoofing.',
    'svg_sanitization' => 'SVG Sanitization',
    'svg_sanitization_description' => 'Automatically removes dangerous scripts and external references from SVG files. If disabled, dangerous SVGs will be rejected.',
    'zip_security' => 'ZIP Security Check',
    'zip_security_description' => 'Checks compression ratio and file count to prevent ZIP bombs.',
    'zip_max_compression_ratio' => 'Max Compression Ratio',
    'zip_compression_ratio_help' => 'Uncompressed size / Compressed size limit (ZIP bomb protection)',
    'zip_max_file_count' => 'Max File Count',
    'times' => 'times',
    'files' => 'files',

    'success' => [
        'settings_updated' => 'Media settings updated successfully.',
    ],
];
