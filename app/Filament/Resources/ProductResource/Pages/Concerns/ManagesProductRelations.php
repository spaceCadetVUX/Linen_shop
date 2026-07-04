<?php

namespace App\Filament\Resources\ProductResource\Pages\Concerns;

use App\Models\FilterGroup;
use Illuminate\Support\Str;

/**
 * Logic lưu translations + filter values dùng chung cho CreateProduct và EditProduct.
 */
trait ManagesProductRelations
{
    protected function saveTranslations(): void
    {
        $record           = $this->getRecord();
        $translationsData = $this->data['translations'] ?? [];

        foreach (config('app.supported_locales') as $locale) {
            $localeData = $translationsData[$locale] ?? [];

            if (empty($localeData['name'])) {
                continue;
            }

            $updateData = collect($localeData)
                ->only(['name', 'slug', 'short_description', 'description', 'price', 'sale_price', 'currency'])
                ->map(fn ($v) => $v === '' ? null : $v)
                ->toArray();

            // Slug NOT NULL trên product_translations — derive từ name nếu bị bỏ trống
            if (empty($updateData['slug'])) {
                $updateData['slug'] = Str::slug($localeData['name']);
            }

            $record->translations()->updateOrCreate(['locale' => $locale], $updateData);
        }
    }

    protected function saveFilterValues(): void
    {
        $allSelectedIds = [];

        foreach (FilterGroup::active()->pluck('id') as $groupId) {
            $selected = $this->data["filter_group_{$groupId}"] ?? [];
            array_push($allSelectedIds, ...$selected);
        }

        $this->getRecord()->filterValues()->sync($allSelectedIds);
    }

    /**
     * Đẩy lại product lên Meilisearch sau khi translations + filter values đã lưu.
     * Scout chỉ tự re-index khi bảng products thay đổi — pivot sync() và
     * translation updateOrCreate() không trigger observer nào của Product.
     */
    protected function syncSearchIndex(): void
    {
        $this->getRecord()->searchable();
    }
}
