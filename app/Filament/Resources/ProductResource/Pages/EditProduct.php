<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\Concerns\ManagesProductRelations;
use App\Models\FilterGroup;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditProduct extends EditRecord
{
    use ManagesProductRelations;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleActive')
                ->label(fn () => $this->record->is_active ? 'Hide product' : 'Show product')
                ->icon(fn () => $this->record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color(fn () => $this->record->is_active ? 'warning' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->is_active ? 'Hide this product?' : 'Show this product?')
                ->modalDescription(fn () => $this->record->is_active
                    ? 'Product will be hidden from storefront immediately.'
                    : 'Product will be visible on storefront immediately.')
                ->action(function () {
                    $this->record->update(['is_active' => ! $this->record->is_active]);
                    $this->refreshFormData(['is_active']);
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        foreach (config('app.supported_locales') as $locale) {
            $translation = $record->translations()->where('locale', $locale)->first();

            if ($translation) {
                $data['translations'][$locale] = $translation->only([
                    'name', 'slug', 'short_description', 'description', 'info_sections',
                    'price', 'sale_price', 'currency',
                ]);
            }
        }

        // Pre-populate per-group CheckboxLists
        $selectedIds = $record->filterValues()->pluck('filter_values.id')->toArray();
        foreach (FilterGroup::active()->with('activeValues')->get() as $group) {
            $groupValueIds = $group->activeValues->pluck('id')->toArray();
            $data["filter_group_{$group->id}"] = array_values(
                array_intersect($selectedIds, $groupValueIds)
            );
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->captureCategoryIdsBeforeSave();

        $vi = $data['translations']['vi'] ?? [];

        if (filled($vi['name'] ?? null)) {
            $data['name'] = $vi['name'];
            $data['slug'] = filled($vi['slug'] ?? null) ? $vi['slug'] : Str::slug($vi['name']);
            $data['short_description'] = $vi['short_description'] ?? null;
            $data['description'] = $vi['description'] ?? null;
            // price / stock_quantity NOT NULL trên products — bỏ trống → giữ giá trị an toàn
            $data['price'] = filled($vi['price'] ?? null) ? $vi['price'] : 0;
            $data['sale_price'] = filled($vi['sale_price'] ?? null) ? $vi['sale_price'] : null;
            $data['currency'] = $vi['currency'] ?? 'VND';
        }

        if (array_key_exists('stock_quantity', $data) && ! filled($data['stock_quantity'])) {
            $data['stock_quantity'] = 0;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->saveTranslations();
        $this->saveFilterValues();
        $this->syncSearchIndex();
        $this->syncCategoryJsonld();
    }
}
