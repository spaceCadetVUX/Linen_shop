<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SitemapIndexResource\Pages;
use App\Models\Seo\SitemapIndex;
use App\Services\Seo\SitemapService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SitemapIndexResource extends Resource
{
    protected static ?string $model = SitemapIndex::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.seo_geo');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.sitemap_index');
    }

    // No create/edit — seeded data only
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Placeholder::make('info')
                ->content('Sitemap indexes are managed programmatically. Use the Regenerate action in the table.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.sitemap_index.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('filename')
                    ->label(__('admin.sitemap_index.fields.file'))
                    ->url(fn (SitemapIndex $record): string => url($record->filename))
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconPosition(IconPosition::After)
                    ->copyable()
                    ->copyMessage('URL copied'),

                TextColumn::make('entry_count')
                    ->label(__('admin.sitemap_index.fields.entries'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('last_generated_at')
                    ->label(__('admin.sitemap_index.fields.last_generated'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('admin.sitemap_index.fields.never_placeholder')),

                IconColumn::make('is_active')
                    ->label(__('admin.sitemap_index.fields.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.sitemap_index.fields.active')),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('admin.sitemap_index.actions.view'))
                    ->icon('heroicon-o-globe-alt')
                    ->color('gray')
                    ->url(fn (SitemapIndex $record): string => url($record->filename))
                    ->openUrlInNewTab(),

                Action::make('regenerate')
                    ->label(__('admin.sitemap_index.actions.regenerate'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.sitemap_index.actions.regenerate_modal_heading'))
                    ->modalDescription(__('admin.sitemap_index.actions.regenerate_modal_description'))
                    ->action(function (SitemapIndex $record, SitemapService $service): void {
                        $service->generateChild($record);

                        Notification::make()
                            ->title(__('admin.sitemap_index.notifications.regenerated', ['file' => $record->filename]))
                            ->success()
                            ->send();
                    }),

                Action::make('toggleActive')
                    ->label(fn (SitemapIndex $record): string => $record->is_active
                        ? __('admin.sitemap_index.actions.deactivate')
                        : __('admin.sitemap_index.actions.activate'))
                    ->icon(fn (SitemapIndex $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (SitemapIndex $record): string => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (SitemapIndex $record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSitemapIndexes::route('/'),
        ];
    }
}
