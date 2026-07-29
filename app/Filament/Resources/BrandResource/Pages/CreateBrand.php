<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected bool $mcpProtected = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->mcpProtected = (bool) ($data['mcp_protected'] ?? false);
        unset($data['mcp_protected']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach (config('app.supported_locales') as $locale) {
            $this->record->seoMetas()->updateOrCreate(
                ['locale' => $locale],
                ['is_mcp_protected' => $this->mcpProtected]
            );
        }
    }
}
