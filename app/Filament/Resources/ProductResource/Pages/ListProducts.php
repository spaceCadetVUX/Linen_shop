<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\BusinessProfileResource;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('productFallbackSettings')
                ->label('Product Fallback Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(BusinessProfileResource::getUrl() . '?tab=page-fallbacks')
                ->openUrlInNewTab(),

            Actions\CreateAction::make(),
        ];
    }
}
