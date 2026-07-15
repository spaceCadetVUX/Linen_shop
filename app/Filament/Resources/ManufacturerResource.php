<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManufacturerResource\Pages;
use App\Models\Manufacturer;
use App\Support\LocaleUrl;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ManufacturerResource extends Resource
{
    protected static ?string $model = Manufacturer::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.manufacturer');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            // ── General ───────────────────────────────────────────────────────
            Section::make(__('admin.manufacturer.sections.general'))
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->live(debounce: 500)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))
                        ),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(table: Manufacturer::class, column: 'slug', ignoreRecord: true),

                    Forms\Components\TextInput::make('website')
                        ->url()
                        ->placeholder(__('admin.manufacturer.fields.website_placeholder'))
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('country')
                        ->placeholder(__('admin.manufacturer.fields.country_placeholder'))
                        ->hint(__('admin.manufacturer.fields.country_hint'))
                        ->hintIcon('heroicon-o-code-bracket')
                        ->hintColor('info')
                        ->helperText(__('admin.manufacturer.fields.country_help'))
                        ->maxLength(2)
                        ->minLength(2)
                        ->rules(['regex:/^[A-Za-z]{2}$/'])
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper(trim($state)) : null)
                        ->validationMessages([
                            'regex' => __('admin.manufacturer.validation.country_regex'),
                            'min' => __('admin.manufacturer.validation.country_length'),
                            'max' => __('admin.manufacturer.validation.country_length'),
                        ]),

                    Forms\Components\FileUpload::make('logo')
                        ->label(__('admin.manufacturer.fields.logo'))
                        ->disk('public')
                        ->directory('manufacturers')
                        ->image()
                        ->imagePreviewHeight('80'),

                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText(__('admin.manufacturer.fields.sort_order_help'))
                        ->minValue(0),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),

            // ── SEO ───────────────────────────────────────────────────────────
            Group::make()
                ->relationship('seoMetaVi')
                ->schema([
                    Section::make(__('admin.manufacturer.sections.seo'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Forms\Components\TextInput::make('meta_title')
                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.manufacturer.fields.meta_title').'</span>'))
                                ->maxLength(70)
                                ->placeholder(__('admin.manufacturer.fields.meta_title_placeholder'))
                                ->hint(__('admin.manufacturer.fields.meta_title_placeholder'))
                                ->hintIcon('heroicon-o-sparkles')
                                ->hintColor('info')
                                ->helperText(__('admin.manufacturer.fields.meta_title_help'))
                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                    if (empty($state) && $livewire->record?->name) {
                                        $set('meta_title', $livewire->record->name);
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('meta_description')
                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.manufacturer.fields.meta_description').'</span>'))
                                ->rows(3)
                                ->maxLength(160)
                                ->placeholder(__('admin.manufacturer.fields.meta_description_placeholder'))
                                ->hint(__('admin.manufacturer.fields.meta_description_placeholder'))
                                ->hintIcon('heroicon-o-sparkles')
                                ->hintColor('info')
                                ->helperText(__('admin.manufacturer.fields.meta_description_help'))
                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                    if (empty($state) && $livewire->record?->description) {
                                        $set('meta_description', $livewire->record->description);
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('canonical_url')
                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.manufacturer.fields.canonical_url').'</span>'))
                                ->url()
                                ->placeholder(__('admin.manufacturer.fields.canonical_url_placeholder'))
                                ->hint(__('admin.manufacturer.fields.canonical_url_hint'))
                                ->hintIcon('heroicon-o-sparkles')
                                ->hintColor('info')
                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                    if (empty($state) && $livewire->record?->slug) {
                                        $set('canonical_url', LocaleUrl::for('manufacturer', $livewire->record->slug, 'vi'));
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\Select::make('robots')
                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.manufacturer.fields.robots').'</span>'))
                                ->options([
                                    'index,follow' => 'index, follow (default)',
                                    'noindex,follow' => 'noindex,follow',
                                    'index,nofollow' => 'index,nofollow',
                                    'noindex,nofollow' => 'noindex,nofollow',
                                ])
                                ->default('index,follow')
                                ->native(false),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->collapsed(),
                ])
                ->columnSpanFull(),

            Group::make()
                ->relationship('seoMetaEn')
                ->schema([
                    Forms\Components\Hidden::make('locale')->default('en'),

                    Section::make(__('admin.manufacturer.sections.seo_en'))
                        ->icon('heroicon-o-language')
                        ->schema([
                            Forms\Components\TextInput::make('canonical_url')
                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.manufacturer.fields.canonical_url_en').'</span>'))
                                ->url()
                                ->placeholder(__('admin.manufacturer.fields.canonical_url_en_placeholder'))
                                ->hint(__('admin.manufacturer.fields.canonical_url_hint'))
                                ->hintIcon('heroicon-o-sparkles')
                                ->hintColor('info')
                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                    if (empty($state) && $livewire->record?->slug) {
                                        $set('canonical_url', LocaleUrl::for('manufacturer', $livewire->record->slug, 'en'));
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\Select::make('robots')
                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.manufacturer.fields.robots').'</span>'))
                                ->options([
                                    'index,follow' => 'index, follow (default)',
                                    'noindex,follow' => 'noindex,follow',
                                    'index,nofollow' => 'index,nofollow',
                                    'noindex,nofollow' => 'noindex,nofollow',
                                ])
                                ->default('index,follow')
                                ->native(false),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->collapsed(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->disk('public')
                    ->height(40),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('country')
                    ->placeholder(__('admin.manufacturer.fields.dash_placeholder'))
                    ->badge(),

                Tables\Columns\TextColumn::make('website')
                    ->url(fn (Manufacturer $record): ?string => $record->website ?: null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->placeholder(__('admin.manufacturer.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('admin.manufacturer.fields.products'))
                    ->counts('products')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.manufacturer.fields.sort_order'))
                    ->sortable()
                    ->alignCenter()
                    ->width('80px'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.manufacturer.fields.active')),
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
            'index' => Pages\ListManufacturers::route('/'),
            'create' => Pages\CreateManufacturer::route('/create'),
            'edit' => Pages\EditManufacturer::route('/{record}/edit'),
        ];
    }
}
