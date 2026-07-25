<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Contracts\Core;

/**
 * Builds and canonicalizes the Core integrity manifest.
 *
 * Shared between the verifier (Core, public-key only) and the signer
 * (DixlaseSigner, private key) so the hashed file set and the canonical
 * signing bytes are guaranteed identical on both sides. The hashing logic
 * uses only the public key / no key at all, so it is safe in Core.
 */
interface CoreManifestBuilderInterface
{
    /**
     * SHA-256 hash map of the curated first-party file set.
     *
     * @param  string|null  $basePath  core root override (testing); defaults to base_path()
     * @return array<string, string> relative path => "sha256:hash", ksorted
     */
    public function fileHashes(?string $basePath = null): array;

    /**
     * The manifest document WITHOUT the `signing` section (version + files).
     *
     * @param  string|null  $basePath  core root override (testing); defaults to base_path()
     * @return array<string, mixed>
     */
    public function build(?string $basePath = null): array;

    /**
     * Canonical JSON bytes to sign/verify: drop `signing`, ksort top-level and
     * `files`, encode with JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE.
     * Must match PluginSigner::getDataToSign() semantics exactly.
     *
     * @param  array<string, mixed>  $manifest
     */
    public function canonicalize(array $manifest): string;
}
