<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeoEntityProfileResource\Pages;
use App\Models\Seo\GeoEntityProfile;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GeoEntityProfileResource extends Resource
{
    protected static ?string $model = GeoEntityProfile::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 20;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.seo_geo');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.geo_entity_profile');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('model');
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->tabs([

                    // ── Tab 1: Entity ─────────────────────────────────────────
                    Tab::make(__('admin.geo_entity_profile.tabs.entity_context'))
                        ->schema([
                            Forms\Components\TextInput::make('model_type')
                                ->label(__('admin.geo_entity_profile.fields.model_type'))
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('model_display_name')
                                ->label(__('admin.geo_entity_profile.fields.name'))
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($component, GeoEntityProfile $record) {
                                    $component->state(
                                        $record->model?->getAttribute('name')
                                        ?? $record->model?->getAttribute('title')
                                        ?? $record->model_id
                                    );
                                }),

                            Forms\Components\Textarea::make('ai_summary')
                                ->label(__('admin.geo_entity_profile.fields.ai_summary'))
                                ->rows(4)
                                ->helperText(__('admin.geo_entity_profile.fields.ai_summary_help'))
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('target_audience')
                                ->label(__('admin.geo_entity_profile.fields.target_audience'))
                                ->placeholder(__('admin.geo_entity_profile.fields.target_audience_placeholder'))
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('use_cases')
                                ->label(__('admin.geo_entity_profile.fields.use_cases'))
                                ->rows(3)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('llm_context_hint')
                                ->label(__('admin.geo_entity_profile.fields.llm_context_hint'))
                                ->rows(3)
                                ->helperText(__('admin.geo_entity_profile.fields.llm_context_hint_help'))
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    // ── Tab 2: Key Facts ──────────────────────────────────────
                    Tab::make(__('admin.geo_entity_profile.tabs.key_facts'))
                        ->schema([
                            Forms\Components\Repeater::make('key_facts')
                                ->label(__('admin.geo_entity_profile.fields.key_facts'))
                                ->schema([
                                    Forms\Components\TextInput::make('label')
                                        ->label(__('admin.geo_entity_profile.fields.fact'))
                                        ->required()
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('value')
                                        ->label(__('admin.geo_entity_profile.fields.value'))
                                        ->required()
                                        ->columnSpan(1),
                                ])
                                ->columns(2)
                                ->reorderable()
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => filled($state['label'] ?? '') ? $state['label'] : null)
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 3: FAQ ────────────────────────────────────────────
                    Tab::make(__('admin.geo_entity_profile.tabs.faq'))
                        ->schema([
                            Forms\Components\Repeater::make('faq')
                                ->label(__('admin.geo_entity_profile.fields.faq'))
                                ->schema([
                                    Forms\Components\TextInput::make('question')
                                        ->label(__('admin.geo_entity_profile.fields.question'))
                                        ->required()
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('answer')
                                        ->label(__('admin.geo_entity_profile.fields.answer'))
                                        ->rows(3)
                                        ->required()
                                        ->columnSpanFull(),
                                ])
                                ->addActionLabel(__('admin.geo_entity_profile.actions.add_faq'))
                                ->reorderable()
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),

                ])
                ->columnSpanFull(),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('model_type')
                    ->label(__('admin.geo_entity_profile.fields.model_column'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('model_name')
                    ->label(__('admin.geo_entity_profile.fields.name_column'))
                    ->state(fn (GeoEntityProfile $record): string => (string) ($record->model?->getAttribute('name')
                            ?? $record->model?->getAttribute('title')
                            ?? '—')
                    )
                    ->searchable(false)
                    ->limit(50),

                IconColumn::make('has_summary')
                    ->label(__('admin.geo_entity_profile.fields.summary_column'))
                    ->state(fn (GeoEntityProfile $record): bool => ! empty($record->ai_summary))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('key_facts_count')
                    ->label(__('admin.geo_entity_profile.fields.key_facts_column'))
                    ->state(fn (GeoEntityProfile $record): string => count($record->key_facts ?? []).' facts')
                    ->badge()
                    ->color(fn (GeoEntityProfile $record): string => count($record->key_facts ?? []) > 0 ? 'success' : 'gray'),

                TextColumn::make('faq_count')
                    ->label(__('admin.geo_entity_profile.fields.faq_column'))
                    ->state(fn (GeoEntityProfile $record): string => count($record->faq ?? []).' Q&A')
                    ->badge()
                    ->color(fn (GeoEntityProfile $record): string => count($record->faq ?? []) > 0 ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->label(__('admin.geo_entity_profile.fields.model_type'))
                    ->options(fn () => GeoEntityProfile::query()
                        ->distinct()
                        ->pluck('model_type', 'model_type')
                        ->toArray()
                    ),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeoEntityProfiles::route('/'),
            'edit' => Pages\EditGeoEntityProfile::route('/{record}/edit'),
        ];
    }
}
