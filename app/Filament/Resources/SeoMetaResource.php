<?php

namespace App\Filament\Resources;

use App\Enums\OgType;
use App\Filament\Resources\SeoMetaResource\Pages;
use App\Models\Seo\SeoMeta;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeoMetaResource extends Resource
{
    protected static ?string $model = SeoMeta::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static \UnitEnum|string|null $navigationGroup = 'SEO & GEO';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'SEO Meta';

    protected static bool $shouldRegisterNavigation = false;

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->tabs([

                    // ── Tab 1: Basic SEO ──────────────────────────────────────
                    Tab::make(__('admin.seo_meta.tabs.basic_seo'))
                        ->schema([
                            Forms\Components\TextInput::make('model_type')
                                ->label(__('admin.seo_meta.fields.model_type'))
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('model_id')
                                ->label(__('admin.seo_meta.fields.model_id'))
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('meta_title')
                                ->label(__('admin.seo_meta.fields.meta_title'))
                                ->maxLength(160)
                                ->live(debounce: 300)
                                ->suffix(fn ($state) => strlen($state ?? '') . ' / 160')
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('meta_description')
                                ->label(__('admin.seo_meta.fields.meta_description'))
                                ->maxLength(320)
                                ->rows(3)
                                ->live(debounce: 300)
                                ->hint(fn ($state) => strlen($state ?? '') . ' / 320')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('meta_keywords')
                                ->label(__('admin.seo_meta.fields.meta_keywords'))
                                ->placeholder(__('admin.seo_meta.fields.meta_keywords_placeholder'))
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('canonical_url')
                                ->label(__('admin.seo_meta.fields.canonical_url'))
                                ->url()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('robots')
                                ->label(__('admin.seo_meta.fields.robots'))
                                ->options([
                                    'index,follow'     => 'index,follow',
                                    'noindex,nofollow' => 'noindex,nofollow',
                                    'noindex,follow'   => 'noindex,follow',
                                ])
                                ->default('index,follow'),
                        ])
                        ->columns(2),

                    // ── Tab 2: Open Graph ─────────────────────────────────────
                    Tab::make(__('admin.seo_meta.tabs.open_graph'))
                        ->schema([
                            Forms\Components\TextInput::make('og_title')
                                ->label(__('admin.seo_meta.fields.og_title'))
                                ->maxLength(160)
                                ->live(debounce: 300)
                                ->suffix(fn ($state) => strlen($state ?? '') . ' / 160')
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('og_description')
                                ->label(__('admin.seo_meta.fields.og_description'))
                                ->rows(3)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('og_image')
                                ->label(__('admin.seo_meta.fields.og_image'))
                                ->url()
                                ->placeholder(__('admin.seo_meta.fields.og_image_placeholder'))
                                ->columnSpanFull(),

                            Forms\Components\Select::make('og_type')
                                ->label(__('admin.seo_meta.fields.og_type'))
                                ->options(collect(OgType::cases())->mapWithKeys(
                                    fn (OgType $case) => [$case->value => ucfirst($case->value)]
                                ))
                                ->default(OgType::Website->value),
                        ])
                        ->columns(2),

                    // ── Tab 3: Twitter ────────────────────────────────────────
                    Tab::make(__('admin.seo_meta.tabs.twitter'))
                        ->schema([
                            Forms\Components\Select::make('twitter_card')
                                ->label(__('admin.seo_meta.fields.twitter_card'))
                                ->options([
                                    'summary'             => 'Summary',
                                    'summary_large_image' => 'Summary Large Image',
                                    'app'                 => 'App',
                                    'player'              => 'Player',
                                ])
                                ->default('summary_large_image'),

                            Forms\Components\TextInput::make('twitter_title')
                                ->label(__('admin.seo_meta.fields.twitter_title'))
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('twitter_description')
                                ->label(__('admin.seo_meta.fields.twitter_description'))
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

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
                    ->label(__('admin.seo_meta.fields.model_column'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('model_id')
                    ->label(__('admin.seo_meta.fields.model_id'))
                    ->formatStateUsing(fn ($state) => is_string($state) && strlen($state) > 12
                        ? strtoupper(substr($state, 0, 8)) . '…'
                        : $state
                    )
                    ->copyable(),

                TextColumn::make('meta_title')
                    ->label(__('admin.seo_meta.fields.meta_title'))
                    ->limit(60)
                    ->placeholder(__('admin.seo_meta.fields.dash_placeholder'))
                    ->searchable(),

                TextColumn::make('robots')
                    ->label(__('admin.seo_meta.fields.robots'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'index,follow'     => 'success',
                        'noindex,nofollow' => 'danger',
                        'noindex,follow'   => 'warning',
                        default             => 'gray',
                    }),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->label(__('admin.seo_meta.fields.model_type'))
                    ->options(fn () => SeoMeta::query()
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
            'index' => Pages\ListSeoMeta::route('/'),
            'edit'  => Pages\EditSeoMeta::route('/{record}/edit'),
        ];
    }
}
