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

    protected static ?int $navigationSort = 21;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.size_guide');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('key')
                ->label(__('admin.size_guide.fields.key'))
                ->required()
                ->unique(table: SizeGuide::class, column: 'key', ignoreRecord: true)
                ->helperText(__('admin.size_guide.fields.key_help')),

            Forms\Components\TextInput::make('sort_order')
                ->label(__('admin.size_guide.fields.sort_order'))
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('is_active')
                ->default(true),

            Forms\Components\Toggle::make('is_default')
                ->label(__('admin.size_guide.fields.is_default'))
                ->helperText(__('admin.size_guide.fields.is_default_help'))
                ->default(false),

            Tabs::make('Tabs')
                ->tabs([

                    Tab::make(__('admin.size_guide.tabs.locale_vi'))
                        ->schema([
                            Group::make()
                                ->relationship('translationVi')
                                ->mutateRelationshipDataBeforeCreateUsing(
                                    fn (array $data) => ['locale' => 'vi', ...$data]
                                )
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.size_guide.fields.name_vi').'</span>'))
                                        ->required()
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('body')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.size_guide.fields.body_vi').'</span>'))
                                        ->helperText(__('admin.size_guide.fields.body_vi_help'))
                                        ->plugins([MediaRichEditorPlugin::make()])
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make(__('admin.size_guide.tabs.locale_en'))
                        ->schema([
                            Group::make()
                                ->relationship('translationEn')
                                ->mutateRelationshipDataBeforeCreateUsing(
                                    fn (array $data) => ['locale' => 'en', ...$data]
                                )
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.size_guide.fields.name_en').'</span>'))
                                        ->required()
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('body')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.size_guide.fields.body_en').'</span>'))
                                        ->helperText(__('admin.size_guide.fields.body_en_help'))
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
                    ->label(__('admin.size_guide.fields.name_vi_column'))
                    ->color('success')
                    ->placeholder(__('admin.size_guide.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('translationEn.name')
                    ->label(__('admin.size_guide.fields.name_en_column'))
                    ->color('info')
                    ->placeholder(__('admin.size_guide.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('admin.size_guide.fields.products'))
                    ->counts('products'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_default')
                    ->label(__('admin.size_guide.fields.fallback_column'))
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.size_guide.fields.active')),
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
            'index' => Pages\ListSizeGuides::route('/'),
            'create' => Pages\CreateSizeGuide::route('/create'),
            'edit' => Pages\EditSizeGuide::route('/{record}/edit'),
        ];
    }
}
