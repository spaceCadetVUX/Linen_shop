<?php

namespace App\Enums;

enum HomeEditorialScope: string
{
    case Parents = 'parents';
    case All = 'all';
    case Children = 'children';

    public function label(): string
    {
        return match ($this) {
            self::Parents => 'Chỉ danh mục cha',
            self::All => 'Danh mục cha và con',
            self::Children => 'Chỉ danh mục con',
        };
    }

    /** [value => label] cho Filament Select. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
