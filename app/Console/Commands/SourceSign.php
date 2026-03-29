<?php

/**
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

namespace App\Console\Commands;

use App\Models\ExtensionSource;
use App\Services\Extension\SourceVerifier;
use Illuminate\Console\Command;

class SourceSign extends Command
{
    protected $signature = 'dls:source:sign
        {id : Source ID to sign}
        {--key= : Base64-encoded Ed25519 private key (or set EXTENSION_SOURCE_PRIVATE_KEY env)}';

    protected $description = 'Sign a source as official (requires Ed25519 private key)';

    public function handle(SourceVerifier $verifier): int
    {
        $source = ExtensionSource::query()->find($this->argument('id'));

        if (! $source) {
            $this->error('Source not found.');

            return self::FAILURE;
        }

        $privateKey = $this->option('key') ?? env('EXTENSION_SOURCE_PRIVATE_KEY');

        if (! $privateKey) {
            $this->error('Private key is required. Use --key option or set EXTENSION_SOURCE_PRIVATE_KEY env variable.');

            return self::FAILURE;
        }

        $this->info("Signing source '{$source->name}'...");
        $this->line("Canonical data: {$verifier->getCanonicalData($source)}");

        try {
            $signature = $verifier->sign($source, $privateKey);

            $source->update([
                'official_signature' => $signature,
                'is_official' => true,
            ]);

            $this->info('Source signed successfully.');
            $this->line("Signature: {$signature}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Signing failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
