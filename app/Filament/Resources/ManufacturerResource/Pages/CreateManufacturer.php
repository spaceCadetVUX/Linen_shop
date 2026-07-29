<?php

namespace App\Filament\Resources\ManufacturerResource\Pages;

use App\Filament\Resources\ManufacturerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturer extends CreateRecord
{
    protected static string $resource = ManufacturerResource::class;

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
