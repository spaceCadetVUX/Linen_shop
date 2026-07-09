<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    // Admin can enter a review on a customer's behalf (e.g. relayed via
    // Zalo/phone) — must reflect what that real customer actually said,
    // never fabricated content used to pad the rating.
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
