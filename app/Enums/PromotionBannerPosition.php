<?php

namespace App\Enums;

enum PromotionBannerPosition: string
{
    case Left = 'left';
    case Right = 'right';

    public function label(): string
    {
        return match ($this) {
            self::Left => 'Bên trái',
            self::Right => 'Bên phải',
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
