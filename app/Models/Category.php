<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use App\Traits\HasGeoProfile;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use App\Traits\HasJsonldSchemas;
use App\Traits\HasLlmsEntry;
use App\Traits\HasMedia;
use App\Traits\HasSeoMeta;
use App\Traits\HasSitemapEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasSeoMeta;
    use HasGeoProfile;
    use HasJsonldSchemas;
    use HasSitemapEntry;
    use HasLlmsEntry;
    use HasMedia;
    use HasActivityLog;
    use LogsActivity;

    // keyType must be 'string' so Eloquent binds the PK as a quoted string in
    // PostgreSQL polymorphic queries (seo_meta.model_id, geo_entity_profiles.model_id are varchar(36)).
    public $incrementing = true;

    protected $keyType = 'string';

    // ── Mass assignment ───────────────────────────────────────────────────────

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_path',
        'sort_order',
        'is_active',
        'faq_items_vi',
        'faq_items_en',
        'mcp_drafted_at',
        'mcp_token_id',
    ];

    // ── Activity log ──────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('category')
            ->logOnly(['parent_id', 'slug', 'image_path', 'sort_order', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'deleted_at'     => 'datetime',
            'faq_items_vi'   => 'array',
            'faq_items_en'   => 'array',
            'mcp_drafted_at' => 'datetime',
            'mcp_token_id'   => 'integer',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Whether this category should actually resolve on the storefront.
     *
     * A category with `is_active = true` is still unreachable if its parent is
     * inactive or soft-deleted — `parent()` transparently returns null for a
     * trashed parent (global SoftDeletingScope), so a plain `is_active` check
     * on the child alone was letting orphaned children stay directly
     * accessible via slug even though they'd already vanished from the menu.
     */
    public function isPubliclyVisible(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return is_null($this->parent_id) || (bool) $this->parent?->is_active;
    }

    /**
     * All descendant IDs (children, grandchildren, ...), walked iteratively —
     * cheap at this table's scale, avoids a recursive CTE for a handful of rows.
     *
     * @return array<int, int|string>
     */
    public function descendantIds(): array
    {
        $ids   = [];
        $queue = [$this->getKey()];

        while ($queue) {
            $childIds = static::withTrashed()
                ->where('parent_id', array_shift($queue))
                ->pluck('id')
                ->all();

            foreach ($childIds as $id) {
                if (in_array($id, $ids, true)) {
                    continue; // already visited — guards against a pre-existing cycle
                }
                $ids[]   = $id;
                $queue[] = $id;
            }
        }

        return $ids;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    /** Parent category (self-referencing). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Direct children categories (self-referencing). */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Full ancestor chain from root down to and including this category.
     * Guards against circular parent_id references. Used to build breadcrumbs
     * for both category pages and product pages so they stay consistent.
     */
    public function ancestorChain(): array
    {
        $chain = [];
        $seenIds = [$this->getKey()];
        $cursor = $this;

        while (true) {
            $chain[] = $cursor;
            $cursor->loadMissing('parent.translations');
            $parent = $cursor->getRelationValue('parent');

            if (! $parent || in_array($parent->getKey(), $seenIds, strict: true)) {
                break;
            }

            $seenIds[] = $parent->getKey();
            $cursor = $parent;
        }

        return array_reverse($chain);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    // ── Multilingual ──────────────────────────────────────────────────────────

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function translation(?string $locale = null): ?CategoryTranslation
    {
        $locale ??= app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale)
                ?? $this->translations->firstWhere('locale', config('app.fallback_locale'));
        }

        return $this->translations()->where('locale', $locale)->first()
            ?? $this->translations()->where('locale', config('app.fallback_locale'))->first();
    }
}
