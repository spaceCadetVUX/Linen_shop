<?php

namespace App\Models;

use App\Enums\FilterGroupType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FilterGroup extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'slug',
        'type',
        'is_variant_dimension',
        'sort_order',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_en ?: $model->name);
            }
        });

        // color_hex chỉ có nghĩa trong group màu — đổi type khỏi color thì dọn
        // sạch hex của values, tránh data mồ côi khiến storefront render swatch sai.
        static::saved(function (self $model) {
            if ($model->type !== FilterGroupType::Color) {
                $model->values()->whereNotNull('color_hex')->update(['color_hex' => null]);
            }
        });
    }

    protected $casts = [
        'type' => FilterGroupType::class,
        'is_active' => 'boolean',
        'is_variant_dimension' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(FilterValue::class)->orderBy('sort_order');
    }

    public function activeValues(): HasMany
    {
        return $this->hasMany(FilterValue::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
