<?php

namespace App\Filament\Resources;

use App\Enums\LlmsScope;
use App\Filament\Resources\LlmsDocumentResource\Pages;
use App\Filament\Resources\LlmsDocumentResource\RelationManagers\EntriesRelationManager;
use App\Models\Seo\LlmsDocument;
use App\Services\Seo\LlmsGeneratorService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LlmsDocumentResource extends Resource
{
    protected static ?string $model = LlmsDocument::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'SEO & GEO';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'LLMs Documents';

    protected static bool $shouldRegisterNavigation = false;

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.llms_document.sections.document_details'))
                ->schema([
                    TextEntry::make('name')
                        ->label(__('admin.llms_document.fields.name')),

                    TextEntry::make('slug')
                        ->label(__('admin.llms_document.fields.slug'))
                        ->copyable(),

                    TextEntry::make('scope')
                        ->label(__('admin.llms_document.fields.scope'))
                        ->badge()
                        ->formatStateUsing(fn (LlmsScope $state): string => ucfirst($state->value))
                        ->color(fn (LlmsScope $state): string => match ($state) {
                            LlmsScope::Index => 'primary',
                            LlmsScope::Full  => 'info',
                        }),

                    TextEntry::make('model_type')
                        ->label(__('admin.llms_document.fields.model_type'))
                        ->placeholder(__('admin.llms_document.fields.dash_placeholder')),

                    TextEntry::make('title')
                        ->label(__('admin.llms_document.fields.title'))
                        ->placeholder(__('admin.llms_document.fields.dash_placeholder')),

                    IconEntry::make('is_active')
                        ->label(__('admin.llms_document.fields.active'))
                        ->boolean()
                        ->trueColor('success')
                        ->falseColor('danger'),

                    TextEntry::make('description')
                        ->label(__('admin.llms_document.fields.description'))
                        ->placeholder(__('admin.llms_document.fields.dash_placeholder'))
                        ->columnSpanFull(),

                    TextEntry::make('entry_count')
                        ->label(__('admin.llms_document.fields.entries'))
                        ->numeric(),

                    TextEntry::make('last_generated_at')
                        ->label(__('admin.llms_document.fields.last_generated'))
                        ->dateTime()
                        ->placeholder(__('admin.llms_document.fields.never_placeholder')),
                ])
                ->columns(3),
        ]);
    }

    // ── Form (not used — documents are seeded) ────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.llms_document.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('admin.llms_document.fields.slug'))
                    ->copyable()
                    ->copyMessage('Slug copied')
                    ->color('gray'),

                TextColumn::make('scope')
                    ->label(__('admin.llms_document.fields.scope'))
                    ->badge()
                    ->formatStateUsing(fn (LlmsScope $state): string => ucfirst($state->value))
                    ->color(fn (LlmsScope $state): string => match ($state) {
                        LlmsScope::Index => 'primary',
                        LlmsScope::Full  => 'info',
                    }),

                TextColumn::make('model_type')
                    ->label(__('admin.llms_document.fields.model_type'))
                    ->placeholder(__('admin.llms_document.fields.dash_placeholder'))
                    ->color('gray'),

                TextColumn::make('entry_count')
                    ->label(__('admin.llms_document.fields.entries'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('last_generated_at')
                    ->label(__('admin.llms_document.fields.last_generated'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('admin.llms_document.fields.never_placeholder')),

                IconColumn::make('is_active')
                    ->label(__('admin.llms_document.fields.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.llms_document.fields.active')),

                Tables\Filters\SelectFilter::make('scope')
                    ->options([
                        LlmsScope::Index->value => 'Index',
                        LlmsScope::Full->value  => 'Full',
                    ]),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('regenerate')
                    ->label(__('admin.llms_document.actions.regenerate'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.llms_document.actions.regenerate_modal_heading'))
                    ->modalDescription(__('admin.llms_document.actions.regenerate_modal_description'))
                    ->action(function (LlmsDocument $record, LlmsGeneratorService $service): void {
                        $service->generateDocument($record);

                        Notification::make()
                            ->title(__('admin.llms_document.notifications.regenerated', ['file' => $record->slug . '.txt']))
                            ->success()
                            ->send();
                    }),

                Action::make('toggleActive')
                    ->label(fn (LlmsDocument $record): string => $record->is_active
                        ? __('admin.llms_document.actions.deactivate')
                        : __('admin.llms_document.actions.activate'))
                    ->icon(fn (LlmsDocument $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (LlmsDocument $record): string => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (LlmsDocument $record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            EntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLlmsDocuments::route('/'),
            'view'  => Pages\ViewLlmsDocument::route('/{record}'),
        ];
    }
}
