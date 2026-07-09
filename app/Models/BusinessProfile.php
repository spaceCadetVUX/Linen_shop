<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'tagline',
        'description',
        'logo_path',
        'email',
        'phone',
        'address_line',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'vat_number',
        'currency',
        'founded_year',
        'business_hours',
        'social_links',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'latitude'       => 'float',
            'longitude'      => 'float',
            'founded_year'   => 'integer',
            'business_hours' => 'array',
            'social_links'   => 'array',
            'extra'          => 'array',
        ];
    }

    public static function instance(): static
    {
        return static::find(1) ?? tap(new static, function (self $model) {
            // `id` is not mass-assignable, so firstOrCreate(['id' => 1], ...) would
            // silently drop it and auto-increment a new row on every call that
            // misses — forceFill() bypasses the guard to pin this singleton to id=1.
            $model->forceFill([
                'id'       => 1,
                'name'     => config('app.name', 'My Business'),
                'currency' => 'VND',
            ])->save();
        });
    }
}
