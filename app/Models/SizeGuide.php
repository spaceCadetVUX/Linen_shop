<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SizeGuide extends Model
{
    protected $fillable = [
        'key',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SizeGuideTranslation::class);
    }

    public function translationVi(): HasOne
    {
        return $this->hasOne(SizeGuideTranslation::class)
            ->where('locale', 'vi')
            ->withDefault(['locale' => 'vi']);
    }

    public function translationEn(): HasOne
    {
        return $this->hasOne(SizeGuideTranslation::class)
            ->where('locale', 'en')
            ->withDefault(['locale' => 'en']);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function translation(?string $locale = null): ?SizeGuideTranslation
    {
        $locale ??= app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale)
                ?? $this->translations->firstWhere('locale', config('app.fallback_locale'));
        }

        return $this->translations()->where('locale', $locale)->first()
            ?? $this->translations()->where('locale', config('app.fallback_locale'))->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
