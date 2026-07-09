<?php

namespace App\Filament\Resources\BusinessProfileResource\Pages;

use App\Filament\Resources\BusinessProfileResource;
use App\Models\BusinessProfile;
use Filament\Resources\Pages\EditRecord;

class EditBusinessProfile extends EditRecord
{
    protected static string $resource = BusinessProfileResource::class;

    public function mount(int|string|null $record = null): void
    {
        parent::mount(BusinessProfile::instance()->getKey());
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    // Filament rebuilds `extra` from only the dot-notation fields defined in
    // this resource's form — without this merge, saving here would wipe out
    // sibling keys ('landing', 'mega_menu', 'shop', 'blog_setting',
    // 'analytics_settings') written by the other Filament Pages that share
    // the same `extra` jsonb column.
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['extra'] = array_merge(
            (array) (BusinessProfile::instance()->extra ?? []),
            $data['extra'] ?? []
        );

        return $data;
    }
}
