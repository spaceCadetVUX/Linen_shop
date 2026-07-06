<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SizeGuideResource\Pages;
use App\Forms\Plugins\MediaRichEditorPlugin;
use App\Models\SizeGuide;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class SizeGuideResource extends Resource
{
    protected static ?string $model = SizeGuide::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-table-cells';

    protected static \UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Size Guides';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('key')
                ->label('Key')
                ->required()
                ->unique(table: SizeGuide::class, column: 'key', ignoreRecord: true)
                ->helperText('Internal identifier — e.g. ao-nu, quan-nu, dam. Not shown publicly.'),

            Forms\Components\TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('is_active')
                ->default(true)
                ->columnSpanFull(),

            Tabs::make('Tabs')
                ->tabs([

                    Tab::make('🇻🇳 Tiếng Việt')
                        ->schema([
                            Group::make()
                                ->relationship('translationVi')
                                ->mutateRelationshipDataBeforeCreateUsing(
                                    fn (array $data) => ['locale' => 'vi', ...$data]
                                )
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Tên hướng dẫn (vi)</span>'))
                                        ->required()
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('body')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Nội dung (vi)</span>'))
                                        ->helperText('Dùng nút Table trên toolbar để chèn bảng số đo. Có thể chèn hình minh họa cách đo.')
                                        ->plugins([MediaRichEditorPlugin::make()])
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('🇬🇧 English')
                        ->schema([
                            Group::make()
                                ->relationship('translationEn')
                                ->mutateRelationshipDataBeforeCreateUsing(
                                    fn (array $data) => ['locale' => 'en', ...$data]
                                )
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Name (en)</span>'))
                                        ->required()
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('body')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Body (en)</span>'))
                                        ->helperText('Use the Table toolbar button to insert the measurement table.')
                                        ->plugins([MediaRichEditorPlugin::make()])
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),
                ])
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('translationVi.name')
                    ->label('Name (vi)')
                    ->color('success')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('translationEn.name')
                    ->label('Name (en)')
                    ->color('info')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index'  => Pages\ListSizeGuides::route('/'),
            'create' => Pages\CreateSizeGuide::route('/create'),
            'edit'   => Pages\EditSizeGuide::route('/{record}/edit'),
        ];
    }
}
