<?php

namespace App\Filament\Resources;

use App\Enums\FilterGroupType;
use App\Filament\Resources\FilterGroupResource\Pages;
use App\Models\FilterGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FilterGroupResource extends Resource
{
    protected static ?string $model = FilterGroup::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.filter_group');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    /** Form state có thể là enum instance (fill từ model cast) hoặc string (live update). */
    private static function isColorType(mixed $state): bool
    {
        return ($state instanceof FilterGroupType ? $state : FilterGroupType::tryFrom((string) $state))
            === FilterGroupType::Color;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.filter_group.sections.filter_group'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('admin.filter_group.fields.name_vi'))
                        ->required(),

                    Forms\Components\TextInput::make('name_en')
                        ->label(__('admin.filter_group.fields.name_en')),

                    Forms\Components\Select::make('type')
                        ->label(__('admin.filter_group.fields.type'))
                        ->options(FilterGroupType::options())
                        ->default(FilterGroupType::Text->value)
                        ->required()
                        ->live()
                        ->helperText(__('admin.filter_group.fields.type_help')),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('admin.filter_group.fields.active'))
                        ->default(true),

                    Forms\Components\Toggle::make('is_variant_dimension')
                        ->label(__('admin.filter_group.fields.is_variant_dimension'))
                        ->helperText(__('admin.filter_group.fields.is_variant_dimension_help'))
                        ->default(false),
                ]),

            Section::make(__('admin.filter_group.sections.values'))
                ->schema([
                    Forms\Components\Repeater::make('values')
                        ->relationship('values')
                        ->label('')
                        ->columns(4)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('admin.filter_group.fields.name_vi'))
                                ->required(),

                            Forms\Components\TextInput::make('name_en')
                                ->label(__('admin.filter_group.fields.name_en')),

                            Forms\Components\ColorPicker::make('color_hex')
                                ->label(__('admin.filter_group.fields.value_color'))
                                ->visible(fn (Get $get) => self::isColorType($get('../../type')))
                                ->required(fn (Get $get) => self::isColorType($get('../../type')))
                                ->regex('/^#[0-9A-Fa-f]{6}$/')
                                ->validationMessages(['regex' => __('admin.filter_group.validation.color_regex')]),

                            Forms\Components\Toggle::make('is_active')
                                ->label(__('admin.filter_group.fields.active'))
                                ->default(true)
                                ->inline(false),
                        ])
                        ->orderColumn('sort_order')
                        ->defaultItems(0)
                        ->addActionLabel(__('admin.filter_group.actions.add_value'))
                        ->reorderableWithDragAndDrop()
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.filter_group.fields.name_vi_column'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('admin.filter_group.fields.name_en_column'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin.filter_group.fields.type_column'))
                    ->badge()
                    ->formatStateUsing(fn (FilterGroupType $state) => $state->label())
                    ->color(fn (FilterGroupType $state) => $state === FilterGroupType::Color ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('values_count')
                    ->label(__('admin.filter_group.fields.values_count'))
                    ->counts('values')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.filter_group.fields.active_column'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_variant_dimension')
                    ->label(__('admin.filter_group.fields.variant_column'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.filter_group.fields.updated'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('admin.filter_group.fields.active')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFilterGroups::route('/'),
            'create' => Pages\CreateFilterGroup::route('/create'),
            'edit' => Pages\EditFilterGroup::route('/{record}/edit'),
        ];
    }
}
