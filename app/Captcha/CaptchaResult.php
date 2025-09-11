<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Captcha;

class CaptchaResult
{
    public function __construct(
        public bool $success,
        public ?float $score = null,
        public ?string $action = null,
        public array $errors = [],
        public array $metadata = []
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isValid(): bool
    {
        return $this->success;
    }

    public function getErrorMessage(): string
    {
        if (empty($this->errors)) {
            return 'CAPTCHA verification failed.';
        }
        
        return implode(', ', $this->errors);
    }
}
