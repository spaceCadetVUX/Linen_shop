<?php

namespace App\Filament\Resources\ManufacturerResource\Pages;

use App\Filament\Resources\ManufacturerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManufacturer extends EditRecord
{
    protected static string $resource = ManufacturerResource::class;

    protected bool $mcpProtected = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['mcp_protected'] = (bool) $this->getRecord()->seoMetaVi?->is_mcp_protected;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->mcpProtected = (bool) ($data['mcp_protected'] ?? false);
        unset($data['mcp_protected']);

        return $data;
    }

    protected function afterSave(): void
    {
        foreach (config('app.supported_locales') as $locale) {
            $this->record->seoMetas()->updateOrCreate(
                ['locale' => $locale],
                ['is_mcp_protected' => $this->mcpProtected]
            );
        }
    }
}
