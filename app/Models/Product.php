<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use App\Traits\HasGeoProfile;
use App\Traits\HasJsonldSchemas;
use App\Traits\HasLlmsEntry;
use App\Traits\HasMedia;
use App\Traits\HasSeoMeta;
use App\Traits\HasSitemapEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends Model
{
    use HasActivityLog;
    use HasFactory;
    use HasGeoProfile;
    use HasJsonldSchemas;
    use HasLlmsEntry;
    use HasMedia;
    use HasSeoMeta;
    use HasSitemapEntry;
    use HasUuids;
    use LogsActivity;
    use Searchable;
    use SoftDeletes;

    // ── PK config ─────────────────────────────────────────────────────────────

    protected $keyType = 'string';

    public $incrementing = false;

    // ── Mass assignment ───────────────────────────────────────────────────────

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'brand_id',
        'manufacturer_id',
        'primary_category_id',
        'short_description',
        'description',
        'price',
        'sale_price',
        'currency',
        'stock_quantity',
        'is_active',
        'show_price',
        'show_original_price',
        'faq_items_vi',
        'faq_items_en',
        'mcp_drafted_at',
        'mcp_token_id',
    ];

    // ── Activity log ──────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('product')
            ->logOnly(['price', 'sale_price', 'stock_quantity', 'is_active', 'sku', 'currency', 'brand_id', 'manufacturer_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
            'show_price' => 'boolean',
            'show_original_price' => 'boolean',
            'faq_items_vi' => 'array',
            'faq_items_en' => 'array',
            'deleted_at' => 'datetime',
            'mcp_drafted_at' => 'datetime',
            'mcp_token_id' => 'integer',
        ];
    }

    // ── Scout — Meilisearch ───────────────────────────────────────────────────

    /**
     * Index name in Meilisearch (prefix from scout.prefix config).
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'products';
    }

    /**
     * Fields sent to Meilisearch on every save.
     * Includes all filterable and sortable attributes required by the search index.
     */
    public function toSearchableArray(): array
    {
        // makeAllSearchableUsing() pre-loads these during scout:import; loadMissing
        // covers single-model syncs, where the queued MakeSearchable job restores
        // the model without relations — a relationLoaded() guard here would
        // silently index empty category_ids/filter_value_ids on every admin save.
        $this->loadMissing(['categories', 'filterValues', 'translations']);

        // Per-locale searchable text. Falls back to the base products.* columns
        // when a locale has no translation row yet, so the index never has a
        // blank field for an existing product.
        $localized = [];
        foreach (config('app.supported_locales', ['vi', 'en']) as $locale) {
            $translation = $this->translations->firstWhere('locale', $locale);

            $localized["name_{$locale}"] = $translation->name ?? $this->name;
            $localized["short_description_{$locale}"] = $translation->short_description ?? $this->short_description;
        }

        return array_merge([
            'id' => $this->id,
            'sku' => $this->sku,
            'category_ids' => $this->categories->pluck('id')->all(),
            'categories' => $this->categories->pluck('name')->all(),
            'filter_value_ids' => $this->filterValues->pluck('id')->all(),
            // Display names so a keyword like "trắng" or "linen" matches products
            // by attribute, not just by name/description. FilterValue stores vi in
            // `name` and en in `name_en` (nullable, vi fallback) — no locale table.
            'filter_value_names_vi' => $this->filterValues->pluck('name')->all(),
            'filter_value_names_en' => $this->filterValues->map(fn ($v) => $v->name_en ?: $v->name)->all(),
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            // What the customer actually pays — sale_price when it undercuts price,
            // else price. Indexed separately so price-range filtering is a single
            // numeric comparison instead of an OR across two attributes.
            'effective_price' => ($this->sale_price && $this->sale_price < $this->price)
                ? (float) $this->sale_price
                : (float) $this->price,
            'stock_quantity' => $this->stock_quantity,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->timestamp,
        ], $localized);
    }

    /**
     * Eager-load categories, filterValues and translations when running
     * scout:import to populate category_ids, filter_value_ids/names and the
     * per-locale name_{locale}/short_description_{locale} fields.
     */
    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['categories', 'filterValues', 'translations']);
    }

    /**
     * Only index active, non-deleted products.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active && ! $this->trashed();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Chỉ lấy ảnh đầu tiên (sort_order nhỏ nhất) — dùng cho thumbnail ở list view.
     * Tránh load toàn bộ images collection chỉ để lấy ->first().
     */
    public function thumbnail(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * [primary, hover] ProductImage cho product card (shop grid, category, related).
     * Yêu cầu `images` đã được eager-loaded (quan hệ đã orderBy sort_order sẵn).
     *
     * - 2 ảnh is_card_priority → dùng đúng 2 ảnh đó, thứ tự theo sort_order.
     * - 1 ảnh is_card_priority → primary = ảnh mặc định (sort_order nhỏ nhất), hover = ảnh được chọn.
     * - 0 ảnh is_card_priority → primary/hover = 2 ảnh đầu theo sort_order (hành vi mặc định cũ).
     *
     * @return array{0: ?ProductImage, 1: ?ProductImage}
     */
    public function cardImages(): array
    {
        $images = $this->images;
        $priority = $images->where('is_card_priority', true)->values();

        if ($priority->count() >= 2) {
            return [$priority[0], $priority[1]];
        }

        if ($priority->count() === 1) {
            return [$images->first(), $priority[0]];
        }

        return [$images->get(0), $images->get(1) ?? $images->get(0)];
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    public function optionTypes(): HasMany
    {
        return $this->hasMany(ProductOptionType::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function filterValues(): BelongsToMany
    {
        return $this->belongsToMany(FilterValue::class, 'product_filter_values');
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ── Multilingual ──────────────────────────────────────────────────────────

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    /**
     * Lấy translation theo locale. Nếu không có → fallback về vi.
     * Dùng in-memory collection khi đã eager-load, query DB khi chưa.
     */
    public function translation(?string $locale = null): ?ProductTranslation
    {
        $locale ??= app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale)
                ?? $this->translations->firstWhere('locale', config('app.fallback_locale'));
        }

        return $this->translations()->where('locale', $locale)->first()
            ?? $this->translations()->where('locale', config('app.fallback_locale'))->first();
    }

    /**
     * Giá theo locale — admin nhập riêng. Fallback về products.price.
     */
    public function localizedPrice(?string $locale = null): string
    {
        return $this->translation($locale)?->price ?? $this->price;
    }

    /**
     * Đơn vị tiền theo locale. Fallback về config default_currency.
     */
    public function localizedCurrency(?string $locale = null): string
    {
        return $this->translation($locale)?->currency ?? config('app.default_currency', 'VND');
    }
}
