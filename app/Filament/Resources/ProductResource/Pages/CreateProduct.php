<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\Concerns\ManagesProductRelations;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    use ManagesProductRelations;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vi = $data['translations']['vi'] ?? [];

        $data['name']              = $vi['name'] ?? null;
        $data['slug']              = filled($vi['slug'] ?? null) ? $vi['slug'] : Str::slug($vi['name'] ?? '');
        $data['short_description'] = $vi['short_description'] ?? null;
        $data['description']       = $vi['description'] ?? null;
        // price / stock_quantity NOT NULL trên products — draft (inactive) bỏ trống → 0
        $data['price']             = filled($vi['price'] ?? null) ? $vi['price'] : 0;
        $data['sale_price']        = filled($vi['sale_price'] ?? null) ? $vi['sale_price'] : null;
        $data['currency']          = $vi['currency'] ?? 'VND';
        $data['stock_quantity']    = filled($data['stock_quantity'] ?? null) ? $data['stock_quantity'] : 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveTranslations();
        $this->saveFilterValues();
    }
}
