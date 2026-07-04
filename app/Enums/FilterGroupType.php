<?php

namespace App\Enums;

enum FilterGroupType: string
{
    case Text = 'text';
    case Color = 'color';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Thuộc tính thường',
            self::Color => 'Màu sắc (swatch)',
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
