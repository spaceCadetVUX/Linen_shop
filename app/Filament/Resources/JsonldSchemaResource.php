<?php

namespace App\Filament\Resources;

use App\Enums\JsonldSchemaType;
use App\Filament\Resources\JsonldSchemaResource\Pages;
use App\Models\Seo\JsonldSchema;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JsonldSchemaResource extends Resource
{
    protected static ?string $model = JsonldSchema::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?int $navigationSort = 30;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.seo_geo');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.jsonld_schema');
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Forms\Components\TextInput::make('model_type')
                ->label(__('admin.jsonld_schema.fields.model_type'))
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('model_id')
                ->label(__('admin.jsonld_schema.fields.model_id'))
                ->disabled()
                ->dehydrated(false),

            Forms\Components\Select::make('schema_type')
                ->label(__('admin.jsonld_schema.fields.schema_type'))
                ->options(collect(JsonldSchemaType::cases())->mapWithKeys(
                    fn (JsonldSchemaType $case) => [$case->value => $case->value]
                ))
                ->required(),

            Forms\Components\TextInput::make('label')
                ->label(__('admin.jsonld_schema.fields.label'))
                ->required(),

            Forms\Components\TextInput::make('sort_order')
                ->label(__('admin.jsonld_schema.fields.sort_order'))
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('is_active')
                ->label(__('admin.jsonld_schema.fields.active'))
                ->default(true),

            Forms\Components\Toggle::make('is_auto_generated')
                ->label(__('admin.jsonld_schema.fields.auto_generated'))
                ->helperText(__('admin.jsonld_schema.fields.auto_generated_help'))
                ->default(true),

            Forms\Components\Textarea::make('payload')
                ->label(__('admin.jsonld_schema.fields.payload'))
                ->rows(25)
                ->formatStateUsing(fn ($state) => is_array($state)
                    ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $state
                )
                ->dehydrateStateUsing(fn ($state) => is_string($state) && filled($state)
                    ? json_decode($state, true) ?? $state
                    : $state
                )
                ->helperText(__('admin.jsonld_schema.fields.payload_help'))
                ->columnSpanFull(),

        ])->columns(2);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('model_type')
                    ->label(__('admin.jsonld_schema.fields.model_column'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('schema_type')
                    ->label(__('admin.jsonld_schema.fields.schema_type'))
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('label')
                    ->searchable()
                    ->limit(40),

                IconColumn::make('is_active')
                    ->label(__('admin.jsonld_schema.fields.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_auto_generated')
                    ->label(__('admin.jsonld_schema.fields.auto_column'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->trueIcon('heroicon-o-cpu-chip')
                    ->falseIcon('heroicon-o-pencil-square'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->label(__('admin.jsonld_schema.fields.model_type'))
                    ->options(fn () => JsonldSchema::query()
                        ->distinct()
                        ->pluck('model_type', 'model_type')
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('schema_type')
                    ->label(__('admin.jsonld_schema.fields.schema_type'))
                    ->options(collect(JsonldSchemaType::cases())->mapWithKeys(
                        fn (JsonldSchemaType $case) => [$case->value => $case->value]
                    )),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.jsonld_schema.fields.active')),

                Tables\Filters\TernaryFilter::make('is_auto_generated')
                    ->label(__('admin.jsonld_schema.fields.auto_generated')),
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
            'index' => Pages\ListJsonldSchemas::route('/'),
            'edit' => Pages\EditJsonldSchema::route('/{record}/edit'),
        ];
    }
}
