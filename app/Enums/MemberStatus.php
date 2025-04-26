<?php

namespace App\Enums;

enum MemberStatus: int
{
    case Inactive = 0;
    case Active = 1;

    public function label(): string
    {
        return trans('admin.status.' . $this->name);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($status) => [
            $status->value => $status->label()
        ])->toArray();
    }
}
