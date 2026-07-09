<?php

namespace App\Enums;

enum ReviewSort: string
{
    case Newest = 'newest';
    case RatingHigh = 'rating_high';
    case RatingLow = 'rating_low';
    case WithPhotos = 'with_photos';

    public function label(): string
    {
        return match ($this) {
            self::Newest => 'Mới nhất',
            self::RatingHigh => 'Sao cao nhất',
            self::RatingLow => 'Sao thấp nhất',
            self::WithPhotos => 'Có hình ảnh',
        };
    }

    /** [value => label] cho dropdown sort trên PDP. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
