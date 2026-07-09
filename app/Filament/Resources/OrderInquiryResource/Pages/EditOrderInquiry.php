<?php

namespace App\Filament\Resources\OrderInquiryResource\Pages;

use App\Filament\Resources\OrderInquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderInquiry extends EditRecord
{
    protected static string $resource = OrderInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
