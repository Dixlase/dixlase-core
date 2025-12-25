<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'index' => [
        'heading' => 'Media Master',
        'upload_new_file' => 'Upload New File',
        'no_files' => 'No files found',
        'upload_first_file' => 'Upload your first file',
        'download' => 'Download',
        'preview' => 'Preview',
        'delete' => 'Delete',
    ],
    'upload' => [
        'heading' => 'Media Upload',
        'select_file' => 'Select Media File:',
        'drag_drop_text' => 'Drag files here or click to upload',
        'supported_formats' => 'Supported formats:',
        'upload_button' => 'Upload',
    ],
    'preview' => [
        'heading' => 'Media Preview',
        'no_preview' => 'File cannot be previewed.',
        'file_name' => 'File Name:',
        'file_type' => 'File Type:',
        'upload_date' => 'Upload Date:',
        'uploaded_by' => 'Uploaded by:',
        'unknown' => 'Unknown',
        'media_url' => 'Media URL',
        'copy' => 'Copy',
        'copied' => 'Copied',
        'url_description' => 'Use this URL to directly access the media file.',
        'back' => 'Back',
        'download' => 'Download',
        'delete' => 'Delete',
        'delete_confirmation' => 'Delete Confirmation',
        'delete_message' => 'Are you sure you want to delete this media file?',
        'cancel' => 'Cancel',
        'copy_failed' => 'Copy failed. Please manually select and copy the URL.',
    ],
    'settings' => [
        'heading' => 'Media Settings',
        'allowed_file_types' => 'Allowed File Types',
        'max_file_size' => 'Maximum File Size',
        'file_size_range' => '(1MB - 100MB)',
        'save_settings' => 'Save',
        'save_confirmation_title' => 'Media Settings Save Confirmation',
        'save_confirmation_message' => 'Do you want to save the media settings?',
        'save_button' => 'Save',
        'cancel_button' => 'Cancel',
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
    ],
    'search' => [
        'heading' => 'Search & Filter',
        'file_name_placeholder' => 'Search by file name',
        'date_from' => 'Upload Date (From)',
        'date_to' => 'Upload Date (To)',
    ],
    'types' => [
        'image' => 'Image',
        'video' => 'Video',
        'audio' => 'Audio',
        'document' => 'Document',
    ],
    'error' => [
        'file_not_found' => 'File could not be retrieved',
        'save_failed' => 'Failed to save file',
        'file_not_exists' => 'File does not exist',
    ],
    'success' => [
        'uploaded' => 'File uploaded successfully.',
        'settings_updated' => 'Media settings updated.',
    ],
    'security' => [
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
    ],
];
