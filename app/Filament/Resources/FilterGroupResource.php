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

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Filter Groups';

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
            Section::make('Filter Group')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Tên (vi)')
                        ->required(),

                    Forms\Components\TextInput::make('name_en')
                        ->label('Name (en)'),

                    Forms\Components\Select::make('type')
                        ->label('Loại')
                        ->options(FilterGroupType::options())
                        ->default(FilterGroupType::Text->value)
                        ->required()
                        ->live()
                        ->helperText('Màu sắc: mỗi value có ô chọn màu, storefront hiển thị swatch thay vì chữ.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Forms\Components\Toggle::make('is_variant_dimension')
                        ->label('Dùng làm biến thể (Variant)')
                        ->helperText('Bật nếu nhóm này (VD: Color, Size) dùng để sinh SKU/giá/tồn kho riêng ở tab Variants của sản phẩm.')
                        ->default(false),
                ]),

            Section::make('Values')
                ->schema([
                    Forms\Components\Repeater::make('values')
                        ->relationship('values')
                        ->label('')
                        ->columns(4)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Tên (vi)')
                                ->required(),

                            Forms\Components\TextInput::make('name_en')
                                ->label('Name (en)'),

                            Forms\Components\ColorPicker::make('color_hex')
                                ->label('Màu')
                                ->visible(fn (Get $get) => self::isColorType($get('../../type')))
                                ->required(fn (Get $get) => self::isColorType($get('../../type')))
                                ->regex('/^#[0-9A-Fa-f]{6}$/')
                                ->validationMessages(['regex' => 'Màu phải ở dạng #RRGGBB.']),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->orderColumn('sort_order')
                        ->defaultItems(0)
                        ->addActionLabel('+ Add value')
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
                    ->label('Name (vi)')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name (en)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn (FilterGroupType $state) => $state->label())
                    ->color(fn (FilterGroupType $state) => $state === FilterGroupType::Color ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('values_count')
                    ->label('Values')
                    ->counts('values')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_variant_dimension')
                    ->label('Variant')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
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
