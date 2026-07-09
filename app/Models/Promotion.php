<?php

namespace App\Models;

use App\Enums\PromotionBannerPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'title',
        'title_en',
        'banner_image',
        'banner_position',
        'cta_label',
        'cta_label_en',
        'cta_url',
        'product_ids',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'banner_position' => PromotionBannerPosition::class,
            'product_ids' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * Computed run status for the Filament table badge — not persisted.
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'off';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'upcoming';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'ended';
        }

        return 'running';
    }
}
