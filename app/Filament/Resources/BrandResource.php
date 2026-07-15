<?php

namespace App\Filament\Resources;

use App\Enums\OgType;
use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use App\Support\LocaleUrl;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bookmark-square';

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 30;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('BrandTabs')
                ->tabs([

                    // ── General ───────────────────────────────────────────────────
                    Tab::make(__('admin.brand.tabs.general'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))
                                ),

                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->unique(table: Brand::class, column: 'slug', ignoreRecord: true),

                            Forms\Components\TextInput::make('website')
                                ->url()
                                ->placeholder(__('admin.brand.fields.website_placeholder'))
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('description')
                                ->rows(3)
                                ->columnSpanFull(),

                            Forms\Components\FileUpload::make('logo')
                                ->label(__('admin.brand.fields.logo'))
                                ->disk('public')
                                ->directory('brands')
                                ->image()
                                ->imagePreviewHeight('80')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('sort_order')
                                ->numeric()
                                ->default(0)
                                ->helperText(__('admin.brand.fields.sort_order_help'))
                                ->minValue(0),

                            Forms\Components\Toggle::make('is_active')
                                ->default(true),
                        ])
                        ->columns(2),

                    // ── SEO ───────────────────────────────────────────────────────
                    Tab::make(__('admin.brand.tabs.seo'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Tabs::make('SeoLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.brand.tabs.locale_vi'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaVi')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('vi'),

                                                    Section::make(__('admin.brand.sections.meta_tags'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.meta_title_vi').'</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder(__('admin.brand.fields.meta_title_placeholder'))
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText(__('admin.brand.fields.meta_title_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->name) {
                                                                        $set('meta_title', $livewire->record->name);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('meta_description')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.meta_description_vi').'</span>'))
                                                                ->rows(3)
                                                                ->live(debounce: 400)
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 120, 160))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 120, 160))
                                                                ->helperText(__('admin.brand.fields.meta_description_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->description) {
                                                                        $set('meta_description', $livewire->record->description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('meta_keywords')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.meta_keywords_vi').'</span>'))
                                                                ->helperText(__('admin.brand.fields.meta_keywords_help'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.canonical_url_vi').'</span>'))
                                                                ->url()
                                                                ->placeholder(__('admin.brand.fields.canonical_url_vi_placeholder'))
                                                                ->hint(__('admin.brand.fields.canonical_url_auto_hint'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->slug) {
                                                                        $set('canonical_url', LocaleUrl::for('brand', $livewire->record->slug, 'vi'));
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('robots')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.robots').'</span>'))
                                                                ->options([
                                                                    'index,follow' => 'index, follow (default)',
                                                                    'noindex,follow' => 'noindex,follow',
                                                                    'index,nofollow' => 'index,nofollow',
                                                                    'noindex,nofollow' => 'noindex,nofollow',
                                                                ])
                                                                ->default('index,follow')
                                                                ->native(false),
                                                        ])
                                                        ->columns(2),

                                                    Section::make(__('admin.brand.sections.og_vi'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.og_title_vi').'</span>'))
                                                                ->placeholder(__('admin.brand.fields.auto_from_meta_title_vi'))
                                                                ->hint(__('admin.brand.fields.auto_from_meta_title_vi'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $set('og_title', $record?->meta_title ?? $livewire->record?->name);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('og_description')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.og_description_vi').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.brand.fields.auto_from_meta_description_vi'))
                                                                ->hint(__('admin.brand.fields.auto_from_meta_description_vi'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('og_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_image')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.og_image').'</span>'))
                                                                ->url()
                                                                ->placeholder(__('admin.brand.fields.auto_from_logo'))
                                                                ->hint(__('admin.brand.fields.auto_from_logo'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText(__('admin.brand.fields.og_image_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->logo) {
                                                                        $set('og_image', asset('storage/'.$livewire->record->logo));
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('og_type')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.og_type').'</span>'))
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Website->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make(__('admin.brand.sections.twitter_vi'))
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.card_type').'</span>'))
                                                                ->options([
                                                                    'summary' => 'Summary',
                                                                    'summary_large_image' => 'Summary Large Image',
                                                                ])
                                                                ->default('summary_large_image')
                                                                ->native(false),

                                                            Forms\Components\TextInput::make('twitter_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.twitter_title_vi').'</span>'))
                                                                ->placeholder(__('admin.brand.fields.auto_from_meta_title_vi'))
                                                                ->hint(__('admin.brand.fields.auto_from_meta_title_vi'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $set('twitter_title', $record?->meta_title ?? $livewire->record?->name);
                                                                    }
                                                                }),

                                                            Forms\Components\Textarea::make('twitter_description')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.twitter_description_vi').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.brand.fields.auto_from_meta_description_vi'))
                                                                ->hint(__('admin.brand.fields.auto_from_meta_description_vi'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('twitter_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),
                                                ]),
                                        ]),

                                    Tab::make(__('admin.brand.tabs.locale_en'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaEn')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('en'),

                                                    Section::make(__('admin.brand.sections.meta_tags'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.meta_title_en').'</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder(__('admin.brand.fields.meta_title_placeholder'))
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText(__('admin.brand.fields.meta_title_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->name) {
                                                                        $set('meta_title', $livewire->record->name);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('meta_description')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.meta_description_en').'</span>'))
                                                                ->rows(3)
                                                                ->live(debounce: 400)
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 120, 160))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 120, 160))
                                                                ->helperText(__('admin.brand.fields.meta_description_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->description) {
                                                                        $set('meta_description', $livewire->record->description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('meta_keywords')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.meta_keywords_en').'</span>'))
                                                                ->helperText(__('admin.brand.fields.meta_keywords_help'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.canonical_url_en').'</span>'))
                                                                ->url()
                                                                ->placeholder(__('admin.brand.fields.canonical_url_en_placeholder'))
                                                                ->hint(__('admin.brand.fields.canonical_url_auto_hint'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->slug) {
                                                                        $set('canonical_url', LocaleUrl::for('brand', $livewire->record->slug, 'en'));
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('robots')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.robots').'</span>'))
                                                                ->options([
                                                                    'index,follow' => 'index, follow (default)',
                                                                    'noindex,follow' => 'noindex,follow',
                                                                    'index,nofollow' => 'index,nofollow',
                                                                    'noindex,nofollow' => 'noindex,nofollow',
                                                                ])
                                                                ->default('index,follow')
                                                                ->native(false),
                                                        ])
                                                        ->columns(2),

                                                    Section::make(__('admin.brand.sections.og_en'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.og_title_en').'</span>'))
                                                                ->placeholder(__('admin.brand.fields.auto_from_meta_title_en'))
                                                                ->hint(__('admin.brand.fields.auto_from_meta_title_en'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $set('og_title', $record?->meta_title ?? $livewire->record?->name);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('og_description')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.og_description_en').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.brand.fields.auto_from_meta_description_en'))
                                                                ->hint(__('admin.brand.fields.auto_from_meta_description_en'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('og_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_image')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.og_image').'</span>'))
                                                                ->url()
                                                                ->placeholder(__('admin.brand.fields.auto_from_logo'))
                                                                ->hint(__('admin.brand.fields.auto_from_logo'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText(__('admin.brand.fields.og_image_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->logo) {
                                                                        $set('og_image', asset('storage/'.$livewire->record->logo));
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('og_type')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.og_type').'</span>'))
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Website->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make(__('admin.brand.sections.twitter_en'))
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.card_type').'</span>'))
                                                                ->options([
                                                                    'summary' => 'Summary',
                                                                    'summary_large_image' => 'Summary Large Image',
                                                                ])
                                                                ->default('summary_large_image')
                                                                ->native(false),

                                                            Forms\Components\TextInput::make('twitter_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.twitter_title_en').'</span>'))
                                                                ->placeholder(__('admin.brand.fields.auto_from_meta_title_en'))
                                                                ->hint(__('admin.brand.fields.auto_from_meta_title_en'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $set('twitter_title', $record?->meta_title ?? $livewire->record?->name);
                                                                    }
                                                                }),

                                                            Forms\Components\Textarea::make('twitter_description')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.twitter_description_en').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.brand.fields.auto_from_meta_description_en'))
                                                                ->hint(__('admin.brand.fields.auto_from_meta_description_en'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('twitter_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),
                                                ]),
                                        ]),
                                ]),
                        ]),

                    // ── GEO / AI ──────────────────────────────────────────────────
                    Tab::make(__('admin.brand.tabs.geo_ai'))
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Tabs::make('GeoLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.brand.tabs.locale_vi'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileVi')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('vi'),

                                                    Section::make(__('admin.brand.sections.ai_context'))
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.ai_summary_vi').'</span>'))
                                                                ->hint(__('admin.brand.fields.ai_summary_hint'))
                                                                ->rows(4)
                                                                ->placeholder(__('admin.brand.fields.ai_summary_vi_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.use_cases_vi').'</span>'))
                                                                ->hint(__('admin.brand.fields.use_cases_hint'))
                                                                ->rows(3)
                                                                ->placeholder(__('admin.brand.fields.use_cases_vi_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.target_audience_vi').'</span>'))
                                                                ->hint(__('admin.brand.fields.target_audience_hint'))
                                                                ->placeholder(__('admin.brand.fields.target_audience_vi_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.llm_context_hint_vi').'</span>'))
                                                                ->hint(__('admin.brand.fields.llm_context_help'))
                                                                ->rows(2)
                                                                ->columnSpanFull(),
                                                        ]),

                                                    Section::make(__('admin.brand.sections.key_facts_vi'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('key_facts')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('label')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.key_fact_label').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.brand.fields.key_fact_label_placeholder_vi')),
                                                                    Forms\Components\TextInput::make('value')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.key_fact_value').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.brand.fields.key_fact_value_placeholder_vi')),
                                                                ])
                                                                ->columns(2)
                                                                ->addActionLabel(__('admin.brand.actions.add_key_fact'))
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),

                                                    Section::make(__('admin.brand.sections.faq_vi'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('faq')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('question')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.faq_question').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.brand.fields.faq_question_vi_placeholder'))
                                                                        ->columnSpanFull(),
                                                                    Forms\Components\Textarea::make('answer')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.faq_answer').'</span>'))
                                                                        ->required()
                                                                        ->rows(3)
                                                                        ->placeholder(__('admin.brand.fields.faq_answer_vi_placeholder'))
                                                                        ->columnSpanFull(),
                                                                ])
                                                                ->addActionLabel(__('admin.brand.actions.add_faq'))
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),
                                                ]),
                                        ]),

                                    Tab::make(__('admin.brand.tabs.locale_en'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileEn')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('en'),

                                                    Section::make(__('admin.brand.sections.ai_context'))
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.ai_summary_en').'</span>'))
                                                                ->hint(__('admin.brand.fields.ai_summary_hint'))
                                                                ->rows(4)
                                                                ->placeholder(__('admin.brand.fields.ai_summary_en_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.use_cases_en').'</span>'))
                                                                ->hint(__('admin.brand.fields.use_cases_hint'))
                                                                ->rows(3)
                                                                ->placeholder(__('admin.brand.fields.use_cases_en_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.target_audience_en').'</span>'))
                                                                ->hint(__('admin.brand.fields.target_audience_hint'))
                                                                ->placeholder(__('admin.brand.fields.target_audience_en_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.llm_context_hint_en').'</span>'))
                                                                ->hint(__('admin.brand.fields.llm_context_help'))
                                                                ->rows(2)
                                                                ->columnSpanFull(),
                                                        ]),

                                                    Section::make(__('admin.brand.sections.key_facts_en'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('key_facts')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('label')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.key_fact_label').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.brand.fields.key_fact_label_placeholder_en')),
                                                                    Forms\Components\TextInput::make('value')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.key_fact_value').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.brand.fields.key_fact_value_placeholder_en')),
                                                                ])
                                                                ->columns(2)
                                                                ->addActionLabel(__('admin.brand.actions.add_key_fact'))
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),

                                                    Section::make(__('admin.brand.sections.faq_en'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('faq')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('question')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.faq_question').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.brand.fields.faq_question_en_placeholder'))
                                                                        ->columnSpanFull(),
                                                                    Forms\Components\Textarea::make('answer')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.faq_answer').'</span>'))
                                                                        ->required()
                                                                        ->rows(3)
                                                                        ->placeholder(__('admin.brand.fields.faq_answer_en_placeholder'))
                                                                        ->columnSpanFull(),
                                                                ])
                                                                ->addActionLabel(__('admin.brand.actions.add_faq'))
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),
                                                ]),
                                        ]),
                                ]),
                        ]),

                    // ── JSON-LD ───────────────────────────────────────────────────
                    Tab::make(__('admin.brand.tabs.jsonld'))
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Section::make(__('admin.brand.sections.jsonld_how_it_works'))
                                ->schema([
                                    Placeholder::make('jsonld_info')
                                        ->label('')
                                        ->content(new HtmlString('
                                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                <li>Schemas được tạo tự động bởi <strong>BrandObserver</strong> mỗi khi lưu thương hiệu.</li>
                                                <li>Schema đánh dấu <strong>Auto</strong> sẽ bị ghi đè mỗi lần lưu — không sửa payload thủ công.</li>
                                                <li>Để tùy chỉnh payload, tắt <em>Auto Generated</em> trước khi sửa.</li>
                                                <li>Toggle <strong>Active</strong> để bật/tắt schema khỏi <code>&lt;head&gt;</code> của trang.</li>
                                            </ul>
                                        '))
                                        ->columnSpanFull(),
                                ])
                                ->collapsed()
                                ->collapsible(),

                            Tabs::make('JsonldLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.brand.tabs.locale_vi'))
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasVi')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.jsonld_schemas_vi').'</span>'))
                                                ->schema([
                                                    Placeholder::make('schema_header')
                                                        ->label('')
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record) {
                                                                return new HtmlString('');
                                                            }
                                                            $type = is_object($record->schema_type) ? $record->schema_type->value : (string) ($record->schema_type ?? '—');
                                                            $label = e($record->label ?? '');
                                                            $auto = $record->is_auto_generated
                                                                ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#fef9c3;color:#854d0e;">⚡ Auto</span>'
                                                                : '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;">✎ Manual</span>';

                                                            return new HtmlString("
                                                                <div style='display:flex;align-items:center;gap:10px;flex-wrap:wrap;'>
                                                                    <span style='font-weight:700;font-size:0.95rem;color:#1e293b;'>{$type}</span>
                                                                    ".(filled($label) ? "<span style='color:#64748b;font-size:0.85rem;'>— {$label}</span>" : '')."
                                                                    {$auto}
                                                                </div>
                                                            ");
                                                        })
                                                        ->columnSpanFull(),

                                                    Placeholder::make('payload_preview')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.jsonld_payload_preview').'</span>'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">Chưa có payload — lưu thương hiệu để tạo.</em>');
                                                            }
                                                            $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                                            return new HtmlString(
                                                                '<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">'
                                                                .e($json)
                                                                .'</pre>'
                                                            );
                                                        })
                                                        ->columnSpanFull(),

                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.jsonld_active').'</span>'))
                                                        ->inline(false),

                                                    Placeholder::make('schema_updated_at')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.brand.fields.jsonld_last_updated').'</span>'))
                                                        ->content(fn ($record) => $record?->updated_at
                                                            ? $record->updated_at->diffForHumans().' ('.$record->updated_at->format('d/m/Y H:i').')'
                                                            : '—'
                                                        ),
                                                ])
                                                ->itemLabel(fn (array $state): ?string => filled($state['schema_type'] ?? '')
                                                        ? (is_object($state['schema_type']) ? $state['schema_type']->value : (string) $state['schema_type'])
                                                        : null
                                                )
                                                ->collapsed()
                                                ->addable(false)
                                                ->deletable(false)
                                                ->reorderable(false)
                                                ->defaultItems(0)
                                                ->columnSpanFull(),
                                        ]),

                                    Tab::make(__('admin.brand.tabs.locale_en'))
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasEn')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.jsonld_schemas_en').'</span>'))
                                                ->schema([
                                                    Placeholder::make('schema_header')
                                                        ->label('')
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record) {
                                                                return new HtmlString('');
                                                            }
                                                            $type = is_object($record->schema_type) ? $record->schema_type->value : (string) ($record->schema_type ?? '—');
                                                            $label = e($record->label ?? '');
                                                            $auto = $record->is_auto_generated
                                                                ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#fef9c3;color:#854d0e;">⚡ Auto</span>'
                                                                : '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;">✎ Manual</span>';

                                                            return new HtmlString("
                                                                <div style='display:flex;align-items:center;gap:10px;flex-wrap:wrap;'>
                                                                    <span style='font-weight:700;font-size:0.95rem;color:#1e293b;'>{$type}</span>
                                                                    ".(filled($label) ? "<span style='color:#64748b;font-size:0.85rem;'>— {$label}</span>" : '')."
                                                                    {$auto}
                                                                </div>
                                                            ");
                                                        })
                                                        ->columnSpanFull(),

                                                    Placeholder::make('payload_preview')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.jsonld_payload_preview').'</span>'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">No payload yet — save the brand to generate.</em>');
                                                            }
                                                            $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                                            return new HtmlString(
                                                                '<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">'
                                                                .e($json)
                                                                .'</pre>'
                                                            );
                                                        })
                                                        ->columnSpanFull(),

                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.jsonld_active').'</span>'))
                                                        ->inline(false),

                                                    Placeholder::make('schema_updated_at')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.brand.fields.jsonld_last_updated').'</span>'))
                                                        ->content(fn ($record) => $record?->updated_at
                                                            ? $record->updated_at->diffForHumans().' ('.$record->updated_at->format('d/m/Y H:i').')'
                                                            : '—'
                                                        ),
                                                ])
                                                ->itemLabel(fn (array $state): ?string => filled($state['schema_type'] ?? '')
                                                        ? (is_object($state['schema_type']) ? $state['schema_type']->value : (string) $state['schema_type'])
                                                        : null
                                                )
                                                ->collapsed()
                                                ->addable(false)
                                                ->deletable(false)
                                                ->reorderable(false)
                                                ->defaultItems(0)
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                        ]),
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

                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),

                Tables\Columns\TextColumn::make('website')
                    ->url(fn (Brand $record): ?string => $record->website ?: null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->placeholder(__('admin.brand.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('admin.brand.fields.products'))
                    ->counts('products')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.brand.fields.sort_order'))
                    ->sortable()
                    ->alignCenter()
                    ->width('80px'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.brand.fields.active')),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }

    // ── Char counter helpers ──────────────────────────────────────────────────

    private static function charCounter(?string $state, int $min, int $max): string
    {
        $len = mb_strlen($state ?? '');

        return "{$len} / {$max} chars";
    }

    private static function charCounterColor(?string $state, int $min, int $max): string
    {
        $len = mb_strlen($state ?? '');
        if ($len === 0) {
            return 'gray';
        }
        if ($len < $min || $len > $max) {
            return 'warning';
        }

        return 'success';
    }
}
