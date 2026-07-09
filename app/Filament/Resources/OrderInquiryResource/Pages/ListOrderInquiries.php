<?php

namespace App\Filament\Resources\OrderInquiryResource\Pages;

use App\Filament\Resources\OrderInquiryResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderInquiries extends ListRecords
{
    protected static string $resource = OrderInquiryResource::class;

    // No Create button — inquiries only come from the cart popup on the storefront.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
