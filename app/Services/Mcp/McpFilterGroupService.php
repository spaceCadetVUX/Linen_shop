<?php

namespace App\Services\Mcp;

use App\Enums\FilterGroupType;
use App\Models\FilterGroup;
use App\Models\FilterValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class McpFilterGroupService
{
    // ── Public API ─────────────────────────────────────────────────────────────

    public function context(string $slug): array
    {
        return $this->buildContextResponse($this->loadGroup($slug));
    }

    public function list(): array
    {
        return FilterGroup::with('values')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FilterGroup $group) => $this->buildContextResponse($group))
            ->values()
            ->all();
    }

    public function upsert(string $slug, array $data, int $tokenId, bool $dryRun): array
    {
        $preview = null;

        try {
            DB::transaction(function () use ($slug, $data, $tokenId, $dryRun, &$preview) {
                $overwrite = (bool) ($data['overwrite_existing'] ?? false);

                $group = FilterGroup::where('slug', $slug)->first();
                $isNew = ! $group;

                if (! $group) {
                    $group = new FilterGroup([
                        'slug' => $slug,
                        'name' => $data['name'] ?? $slug,
                        'is_active' => false,
                    ]);
                }

                // ── Scalar fields ───────────────────────────────────────────────
                if (array_key_exists('name', $data) && ($isNew || $overwrite || empty($group->name))) {
                    $group->name = $data['name'];
                }
                if (array_key_exists('name_en', $data) && ($isNew || $overwrite || empty($group->name_en))) {
                    $group->name_en = $data['name_en'];
                }
                if (array_key_exists('type', $data)) {
                    $type = FilterGroupType::tryFrom((string) $data['type']);
                    if ($type && ($isNew || $overwrite || $group->type === null)) {
                        $group->type = $type;
                    }
                }
                if (array_key_exists('is_variant_dimension', $data)) {
                    $group->is_variant_dimension = (bool) $data['is_variant_dimension'];
                }
                if (array_key_exists('sort_order', $data)) {
                    $group->sort_order = (int) $data['sort_order'];
                }

                // Never auto-activate via upsert — use /activate endpoint
                if (isset($data['is_active']) && $data['is_active'] === false) {
                    $group->is_active = false;
                }

                $group->slug = $slug;
                $group->mcp_drafted_at = now();
                $group->mcp_token_id = $tokenId;
                $group->save();

                // ── Nested values ────────────────────────────────────────────────
                if (isset($data['values']) && is_array($data['values'])) {
                    $this->writeValues($group, $data['values'], $overwrite);
                }

                $group->refresh()->load('values');
                $preview = $this->buildContextResponse($group);

                if ($dryRun) {
                    throw new \RuntimeException('__mcp_dry_run__');
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== '__mcp_dry_run__') {
                throw $e;
            }
        }

        return ['data' => $preview];
    }

    public function readiness(string $slug): array
    {
        return $this->computeReadiness($this->loadGroup($slug));
    }

    public function activate(string $slug): array
    {
        $group = $this->loadGroup($slug);
        $readiness = $this->computeReadiness($group);

        if (! empty($readiness['blocking_issues'])) {
            abort(422, 'Filter group chưa sẵn sàng để activate: '.implode('; ', $readiness['blocking_issues']));
        }

        $group->update([
            'is_active' => true,
            'mcp_drafted_at' => null,
            'mcp_token_id' => null,
        ]);

        $group->refresh()->load('values');

        return ['data' => $this->buildContextResponse($group)];
    }

    // ── Private: load ────────────────────────────────────────────────────────────

    private function loadGroup(string $slug): FilterGroup
    {
        $group = FilterGroup::with('values')->where('slug', $slug)->first();

        if (! $group) {
            abort(404, "Filter group '{$slug}' not found.");
        }

        return $group;
    }

    // ── Private: write helpers ───────────────────────────────────────────────────

    private function writeValues(FilterGroup $group, array $values, bool $overwrite): void
    {
        foreach ($values as $valueData) {
            $name = trim((string) ($valueData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $lookupSlug = filled($valueData['slug'] ?? null)
                ? Str::slug($valueData['slug'])
                : Str::slug($valueData['name_en'] ?? $name);

            $value = FilterValue::where('filter_group_id', $group->id)
                ->where(fn ($q) => $q->where('slug', $lookupSlug)->orWhere('name', $name))
                ->first();

            $isNewValue = ! $value;

            if (! $value) {
                $value = new FilterValue([
                    'filter_group_id' => $group->id,
                    'name' => $name,
                    'is_active' => true,
                ]);
            }

            if ($isNewValue || $overwrite || empty($value->name)) {
                $value->name = $name;
            }
            if (array_key_exists('name_en', $valueData) && ($isNewValue || $overwrite || empty($value->name_en))) {
                $value->name_en = $valueData['name_en'];
            }
            // color_hex chỉ có nghĩa trong group màu — bỏ qua nếu group không phải type=color,
            // tránh set lại giá trị mà FilterGroup::booted() vừa xoá do đổi type (FilterGroup.php:32-36).
            if ($group->type === FilterGroupType::Color && array_key_exists('color_hex', $valueData)) {
                if ($isNewValue || $overwrite || empty($value->color_hex)) {
                    $value->color_hex = $valueData['color_hex'];
                }
            }
            if (array_key_exists('sort_order', $valueData)) {
                $value->sort_order = (int) $valueData['sort_order'];
            }
            if (array_key_exists('is_active', $valueData)) {
                $value->is_active = (bool) $valueData['is_active'];
            }

            $value->filter_group_id = $group->id;
            $value->save();
        }
    }

    // ── Private: readiness ────────────────────────────────────────────────────────

    private function computeReadiness(FilterGroup $group): array
    {
        $checks = [];
        $blocking = [];
        $warnings = [];
        $score = 0;
        $total = 0;

        $hasName = filled($group->name);
        $checks['has_name'] = ['pass' => $hasName];
        $total++;
        if ($hasName) {
            $score++;
        }
        if (! $hasName) {
            $blocking[] = 'name missing';
        }

        $hasNameEn = filled($group->name_en);
        $checks['has_name_en'] = ['pass' => $hasNameEn];
        $total++;
        if ($hasNameEn) {
            $score++;
        }
        if (! $hasNameEn) {
            $warnings[] = 'name_en chưa có';
        }

        $activeValues = $group->values->where('is_active', true);
        $hasActiveValues = $activeValues->isNotEmpty();
        $checks['has_active_values'] = ['pass' => $hasActiveValues, 'count' => $activeValues->count()];
        $total++;
        if ($hasActiveValues) {
            $score++;
        }
        if (! $hasActiveValues) {
            $blocking[] = 'chưa có value active nào';
        }

        if ($group->type === FilterGroupType::Color) {
            $missingHex = $activeValues->filter(fn (FilterValue $v) => blank($v->color_hex));
            $allHaveHex = $missingHex->isEmpty();
            $checks['color_values_have_hex'] = ['pass' => $allHaveHex, 'missing' => $missingHex->pluck('name')->values()->all()];
            $total++;
            if ($allHaveHex) {
                $score++;
            }
            if (! $allHaveHex) {
                $blocking[] = 'value thuộc group màu thiếu color_hex: '.$missingHex->pluck('name')->join(', ');
            }
        }

        if ($group->is_variant_dimension && ! $hasActiveValues) {
            $warnings[] = 'is_variant_dimension=true nhưng chưa có value active nào — không thể sinh variant';
        }

        $dupes = $group->values
            ->groupBy(fn (FilterValue $v) => Str::lower($v->name))
            ->filter(fn ($rows) => $rows->count() > 1);
        if ($dupes->isNotEmpty()) {
            $warnings[] = 'có value trùng tên: '.$dupes->keys()->join(', ');
        }

        $scorePercent = $total > 0 ? (int) round(($score / $total) * 100) : 0;

        return [
            'slug' => $group->slug,
            'score' => $scorePercent,
            'ready' => empty($blocking),
            'checks' => $checks,
            'blocking_issues' => $blocking,
            'warnings' => $warnings,
        ];
    }

    // ── Private: response builder ─────────────────────────────────────────────────

    private function buildContextResponse(FilterGroup $group): array
    {
        return [
            'slug' => $group->slug,
            'name' => $group->name,
            'name_en' => $group->name_en,
            'type' => $group->type?->value,
            'is_active' => (bool) $group->is_active,
            'is_variant_dimension' => (bool) $group->is_variant_dimension,
            'sort_order' => $group->sort_order,
            'mcp_drafted_at' => $group->mcp_drafted_at?->toIso8601String(),
            'values' => $group->values->map(fn (FilterValue $v) => [
                'slug' => $v->slug,
                'name' => $v->name,
                'name_en' => $v->name_en,
                'color_hex' => $v->color_hex,
                'is_active' => (bool) $v->is_active,
                'sort_order' => $v->sort_order,
            ])->values()->all(),
        ];
    }
}
