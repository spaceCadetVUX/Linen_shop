<?php

namespace App\Filament\Resources;

use App\Enums\FilterGroupType;
use App\Enums\OgType;
use App\Enums\VariantAvailability;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\FilterGroup;
use App\Models\FilterValue;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Audit\ProductAuditService;
use App\Services\Product\VariantGeneratorService;
use App\Services\Seo\JsonldService;
use App\Services\Seo\LlmsGeneratorService;
use App\Support\LocaleUrl;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.product');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->tabs([

                    // ── Tab 1: General ────────────────────────────────────────
                    Tab::make(__('admin.product.tabs.general'))
                        ->schema([
                            Forms\Components\Select::make('categories')
                                ->label(__('admin.product.fields.categories'))
                                ->relationship('categories', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, ?array $state) {
                                    // Clear primary only if it's no longer in the selected list
                                    if (! in_array($get('primary_category_id'), $state ?? [])) {
                                        $set('primary_category_id', null);
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\Select::make('primary_category_id')
                                ->label(__('admin.product.fields.primary_category'))
                                ->helperText(__('admin.product.fields.primary_category_help'))
                                ->options(function (Get $get): array {
                                    $ids = $get('categories');
                                    if (empty($ids)) {
                                        return [];
                                    }

                                    return Category::whereIn('id', $ids)
                                        ->orderBy('sort_order')
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->native(false)
                                ->nullable()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('brand_id')
                                ->label(__('admin.product.fields.brand'))
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false),

                            Forms\Components\Select::make('manufacturer_id')
                                ->label(__('admin.product.fields.manufacturer'))
                                ->relationship('manufacturer', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false),

                            Forms\Components\Select::make('size_guide_id')
                                ->label(__('admin.product.fields.size_guide'))
                                ->relationship(
                                    'sizeGuide',
                                    'key',
                                    modifyQueryUsing: fn (Builder $query) => $query->with('translationVi'),
                                )
                                ->getOptionLabelFromRecordUsing(
                                    fn ($record) => $record->translationVi?->name ?: $record->key
                                )
                                ->helperText(__('admin.product.fields.size_guide_help'))
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false),

                            Forms\Components\TextInput::make('sku')
                                ->label(__('admin.product.fields.sku'))
                                ->required(fn (Get $get): bool => (bool) $get('is_active'))
                                ->unique(table: Product::class, column: 'sku', ignoreRecord: true),

                            Forms\Components\Toggle::make('is_active')
                                ->default(true),

                            Forms\Components\Toggle::make('show_price')
                                ->label(__('admin.product.fields.show_price'))
                                ->helperText(__('admin.product.fields.show_price_help'))
                                ->default(true),
                        ])
                        ->columns(2),

                    // ── Tab 2: Content ────────────────────────────────────────
                    Tab::make(__('admin.product.tabs.content'))
                        ->icon('heroicon-o-language')
                        ->schema([
                            Tabs::make('LocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.product.tabs.locale_vi_full'))
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.vi.name')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.name_vi').'</span>'))
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $set('translations.vi.slug', Str::slug($state ?? ''));
                                                    $set('name', $state);
                                                    $set('slug', Str::slug($state ?? ''));
                                                })
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.vi.slug')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.slug_vi').'</span>'))
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('slug', $state))
                                                ->helperText(__('admin.product.fields.slug_auto_help'))
                                                ->rules([
                                                    fn (?Product $record) => self::uniqueTranslationSlugRule('vi', $record),
                                                    fn (?Product $record) => self::uniqueProductSlugRule($record),
                                                ])
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.vi.short_description')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.short_description_vi').'</span>'))
                                                ->rows(3)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('short_description', $state))
                                                ->columnSpanFull(),

                                            Forms\Components\RichEditor::make('translations.vi.description')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.description_vi').'</span>'))
                                                ->columnSpanFull(),

                                            Forms\Components\Repeater::make('translations.vi.info_sections')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.info_sections_vi').'</span>'))
                                                ->helperText(__('admin.product.fields.info_sections_help'))
                                                ->schema([
                                                    Forms\Components\TextInput::make('title')
                                                        ->label(__('admin.product.fields.info_section_title'))
                                                        ->required()
                                                        ->columnSpanFull(),
                                                    Forms\Components\RichEditor::make('content')
                                                        ->label(__('admin.product.fields.info_section_content'))
                                                        ->required()
                                                        ->columnSpanFull(),
                                                ])
                                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                                ->addActionLabel(__('admin.product.actions.add_info_section'))
                                                ->collapsed()
                                                ->reorderable()
                                                ->reorderableWithDragAndDrop()
                                                ->defaultItems(0)
                                                ->columnSpanFull(),

                                            Section::make(__('admin.product.sections.faq_vi'))
                                                ->description(__('admin.product.sections.faq_description'))
                                                ->schema([
                                                    Forms\Components\Repeater::make('faq_items_vi')
                                                        ->label('')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('question')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.faq_question').'</span>'))
                                                                ->required()
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('answer')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.faq_answer').'</span>'))
                                                                ->rows(2)
                                                                ->required()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->afterStateHydrated(function (Forms\Components\Repeater $component, $record): void {
                                                            $component->state($record?->faq_items_vi ?? []);
                                                        })
                                                        ->maxItems(10)
                                                        ->defaultItems(0)
                                                        ->addActionLabel(__('admin.product.actions.add_faq'))
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsed()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2),

                                    Tab::make(__('admin.product.tabs.locale_en_full'))
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.en.name')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.name_en').'</span>'))
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('translations.en.slug', Str::slug($state ?? '')))
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.en.slug')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.slug_en').'</span>'))
                                                ->helperText(__('admin.product.fields.slug_auto_help'))
                                                ->requiredWith('translations.en.name')
                                                ->rules([
                                                    fn (?Product $record) => self::uniqueTranslationSlugRule('en', $record),
                                                ])
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.en.short_description')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.short_description_en').'</span>'))
                                                ->rows(3)
                                                ->columnSpanFull(),

                                            Forms\Components\RichEditor::make('translations.en.description')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.description_en').'</span>'))
                                                ->columnSpanFull(),

                                            Forms\Components\Repeater::make('translations.en.info_sections')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.info_sections_en').'</span>'))
                                                ->helperText(__('admin.product.fields.info_sections_help'))
                                                ->schema([
                                                    Forms\Components\TextInput::make('title')
                                                        ->label(__('admin.product.fields.info_section_title'))
                                                        ->required()
                                                        ->columnSpanFull(),
                                                    Forms\Components\RichEditor::make('content')
                                                        ->label(__('admin.product.fields.info_section_content'))
                                                        ->required()
                                                        ->columnSpanFull(),
                                                ])
                                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                                ->addActionLabel(__('admin.product.actions.add_info_section'))
                                                ->collapsed()
                                                ->reorderable()
                                                ->reorderableWithDragAndDrop()
                                                ->defaultItems(0)
                                                ->columnSpanFull(),

                                            Section::make(__('admin.product.sections.faq_en'))
                                                ->description(__('admin.product.sections.faq_description'))
                                                ->schema([
                                                    Forms\Components\Repeater::make('faq_items_en')
                                                        ->label('')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('question')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.faq_question').'</span>'))
                                                                ->required()
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('answer')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.faq_answer').'</span>'))
                                                                ->rows(2)
                                                                ->required()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->afterStateHydrated(function (Forms\Components\Repeater $component, $record): void {
                                                            $component->state($record?->faq_items_en ?? []);
                                                        })
                                                        ->maxItems(10)
                                                        ->defaultItems(0)
                                                        ->addActionLabel(__('admin.product.actions.add_faq'))
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsed()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 3: Pricing & Stock ────────────────────────────────
                    Tab::make(__('admin.product.tabs.pricing_stock'))
                        ->schema([

                            Section::make(__('admin.product.sections.pricing_vn'))
                                ->schema([
                                    Forms\Components\Select::make('translations.vi.currency')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.currency_vi').'</span>'))
                                        ->options([
                                            'VND' => '🇻🇳 VND — Vietnamese Đồng',
                                            'USD' => '🇺🇸 USD — US Dollar',
                                            'EUR' => '🇪🇺 EUR — Euro',
                                            'SGD' => '🇸🇬 SGD — Singapore Dollar',
                                            'JPY' => '🇯🇵 JPY — Japanese Yen',
                                            'KRW' => '🇰🇷 KRW — Korean Won',
                                            'CNY' => '🇨🇳 CNY — Chinese Yuan',
                                            'THB' => '🇹🇭 THB — Thai Baht',
                                        ])
                                        ->default('VND')
                                        ->native(false)
                                        ->live()
                                        ->hint(__('admin.product.fields.currency_vi_hint'))
                                        ->hintIcon('heroicon-o-code-bracket')
                                        ->hintColor('info')
                                        ->afterStateUpdated(fn ($state, Set $set) => $set('currency', $state))
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('translations.vi.price')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.price_vi').'</span>'))
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->prefix(fn ($get) => match ($get('translations.vi.currency')) {
                                            'USD' => '$', 'EUR' => '€',
                                            'JPY', 'KRW', 'CNY' => '¥',
                                            'SGD' => 'S$', 'THB' => '฿',
                                            default => '₫',
                                        })
                                        ->required(fn (Get $get): bool => (bool) $get('is_active'))
                                        ->afterStateUpdated(fn ($state, Set $set) => $set('price', $state)),

                                    Forms\Components\TextInput::make('translations.vi.sale_price')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.sale_price_vi').'</span>'))
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->prefix(fn ($get) => match ($get('translations.vi.currency')) {
                                            'USD' => '$', 'EUR' => '€',
                                            'JPY', 'KRW', 'CNY' => '¥',
                                            'SGD' => 'S$', 'THB' => '฿',
                                            default => '₫',
                                        })
                                        ->afterStateUpdated(fn ($state, Set $set) => $set('sale_price', $state)),

                                    Forms\Components\Toggle::make('show_original_price')
                                        ->label(__('admin.product.fields.show_original_price'))
                                        ->helperText(__('admin.product.fields.show_original_price_help'))
                                        ->default(true)
                                        ->inline(false)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Section::make(__('admin.product.sections.pricing_en'))
                                ->schema([
                                    Forms\Components\Select::make('translations.en.currency')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.currency_en').'</span>'))
                                        ->options([
                                            'VND' => '🇻🇳 VND — Vietnamese Đồng',
                                            'USD' => '🇺🇸 USD — US Dollar',
                                            'EUR' => '🇪🇺 EUR — Euro',
                                            'SGD' => '🇸🇬 SGD — Singapore Dollar',
                                            'JPY' => '🇯🇵 JPY — Japanese Yen',
                                            'KRW' => '🇰🇷 KRW — Korean Won',
                                            'CNY' => '🇨🇳 CNY — Chinese Yuan',
                                            'THB' => '🇹🇭 THB — Thai Baht',
                                        ])
                                        ->default('USD')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('translations.en.price')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.price_en').'</span>'))
                                        ->numeric()
                                        ->prefix(fn ($get) => match ($get('translations.en.currency')) {
                                            'EUR' => '€',
                                            'JPY', 'KRW', 'CNY' => '¥',
                                            'SGD' => 'S$', 'THB' => '฿',
                                            'VND' => '₫',
                                            default => '$',
                                        }),

                                    Forms\Components\TextInput::make('translations.en.sale_price')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.sale_price_en').'</span>'))
                                        ->numeric()
                                        ->prefix(fn ($get) => match ($get('translations.en.currency')) {
                                            'EUR' => '€',
                                            'JPY', 'KRW', 'CNY' => '¥',
                                            'SGD' => 'S$', 'THB' => '฿',
                                            'VND' => '₫',
                                            default => '$',
                                        }),
                                ])
                                ->columns(2),

                            Section::make(__('admin.product.sections.stock'))
                                ->schema([
                                    Forms\Components\TextInput::make('stock_quantity')
                                        ->label(__('admin.product.fields.stock_quantity'))
                                        ->numeric()
                                        ->default(0)
                                        ->required(fn (Get $get): bool => (bool) $get('is_active'))
                                        ->columnSpanFull(),
                                ]),

                        ]),

                    // ── Tab 3: Images ─────────────────────────────────────────
                    Tab::make(__('admin.product.tabs.images'))
                        ->schema([
                            Forms\Components\Repeater::make('images')
                                ->relationship()
                                ->schema([
                                    Forms\Components\FileUpload::make('path')
                                        ->label(__('admin.product.fields.image'))
                                        ->disk('public')
                                        ->visibility('public')
                                        ->directory(fn () => 'products/'.now()->format('Y/m'))
                                        ->getUploadedFileNameForStorageUsing(function ($file): string {
                                            $dir = 'products/'.now()->format('Y/m');
                                            $name = Str::slug(
                                                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                                            );
                                            $ext = strtolower($file->getClientOriginalExtension());

                                            // Fallback if slug is empty (e.g. all special chars)
                                            if (empty($name)) {
                                                $name = 'image-'.now()->format('YmdHis');
                                            }

                                            $filename = "{$name}.{$ext}";
                                            $counter = 1;

                                            while (Storage::disk('public')->exists("{$dir}/{$filename}")) {
                                                $filename = "{$name}-{$counter}.{$ext}";
                                                $counter++;
                                            }

                                            return $filename;
                                        })
                                        ->hint(__('admin.product.fields.image_filename_hint'))
                                        ->hintIcon('heroicon-o-information-circle')
                                        ->hintColor('success')
                                        ->image()
                                        ->imagePreviewHeight('120')
                                        ->imageEditor()
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('alt_text')
                                        ->label(__('admin.product.fields.alt_text'))
                                        ->columnSpan(1),

                                    Forms\Components\Checkbox::make('is_card_priority')
                                        ->label(__('admin.product.fields.card_priority'))
                                        ->helperText(__('admin.product.fields.card_priority_help'))
                                        ->live()
                                        ->afterStateUpdated(function (bool $state, Set $set, ?Forms\Components\Checkbox $component) {
                                            if (! $state || ! $component) {
                                                return;
                                            }

                                            $checked = collect($component->getParentRepeater()?->getRawState() ?? [])
                                                ->pluck('is_card_priority')
                                                ->filter()
                                                ->count();

                                            if ($checked > 2) {
                                                $set('is_card_priority', false);

                                                Notification::make()
                                                    ->title(__('admin.product.notifications.card_priority_limit_title'))
                                                    ->warning()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpanFull(),

                                ])
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->reorderableWithDragAndDrop()
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 5: Videos ─────────────────────────────────────────
                    // Temporarily locked — see Placeholder below. Original fields
                    // kept commented so the feature can be restored without rework.
                    Tab::make(__('admin.product.tabs.videos'))
                        ->schema([
                            Placeholder::make('videos_locked_notice')
                                ->label('')
                                ->content('Tính năng này đang tạm khóa. Liên hệ dev để biết thêm thông tin.')
                                ->columnSpanFull(),

                            // Forms\Components\Repeater::make('videos')
                            //     ->relationship()
                            //     ->schema([
                            //         // ── Files ─────────────────────────────────
                            //         Forms\Components\FileUpload::make('path')
                            //             ->label('Video File')
                            //             ->disk('public')
                            //             ->directory(fn () => 'products/'.now()->format('Y/m'))
                            //             ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                            //             ->required()
                            //             ->columnSpan(1),
                            //
                            //         Forms\Components\FileUpload::make('thumbnail_path')
                            //             ->label('Thumbnail')
                            //             ->disk('public')
                            //             ->directory(fn () => 'products/'.now()->format('Y/m'))
                            //             ->image()
                            //             ->imagePreviewHeight('100')
                            //             ->columnSpan(1),
                            //
                            //         // ── SEO fields ────────────────────────────
                            //         Forms\Components\TextInput::make('title')
                            //             ->label('Title')
                            //             ->maxLength(255)
                            //             ->hint('Required for VideoObject rich results')
                            //             ->hintIcon('heroicon-o-magnifying-glass')
                            //             ->hintColor('warning')
                            //             ->columnSpan(2),
                            //
                            //         Forms\Components\Textarea::make('description')
                            //             ->label('Description')
                            //             ->rows(2)
                            //             ->hint('Required for VideoObject rich results')
                            //             ->hintIcon('heroicon-o-magnifying-glass')
                            //             ->hintColor('warning')
                            //             ->columnSpan(2),
                            //
                            //         Forms\Components\TextInput::make('duration')
                            //             ->label('Duration (ISO 8601)')
                            //             ->placeholder('PT2M30S')
                            //             ->hint('e.g. PT30S = 30s, PT2M30S = 2m30s, PT1H = 1h')
                            //             ->hintIcon('heroicon-o-clock')
                            //             ->hintColor('info')
                            //             ->columnSpan(2),
                            //     ])
                            //     ->orderColumn('sort_order')
                            //     ->reorderable()
                            //     ->reorderableWithDragAndDrop()
                            //     ->defaultItems(0)
                            //     ->columns(2)
                            //     ->columnSpanFull(),
                        ]),

                    // ── Tab 6: Attributes ─────────────────────────────────────
                    Tab::make(__('admin.product.tabs.attributes'))
                        ->icon('heroicon-o-list-bullet')
                        ->hidden()
                        ->schema([
                            Forms\Components\Repeater::make('attributes')
                                ->relationship()
                                ->label('')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.attribute_name_vi').'</span>'))
                                        ->placeholder(__('admin.product.fields.attribute_name_vi_placeholder'))
                                        ->required()
                                        ->live(debounce: 300)
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('name_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.attribute_name_en').'</span>'))
                                        ->placeholder(__('admin.product.fields.attribute_name_en_placeholder'))
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('value')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.attribute_value_vi').'</span>'))
                                        ->placeholder(__('admin.product.fields.attribute_value_vi_placeholder'))
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('value_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.attribute_value_en').'</span>'))
                                        ->placeholder(__('admin.product.fields.attribute_value_en_placeholder'))
                                        ->columnSpan(1),
                                ])
                                ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? '')
                                        ? ($state['name'].(filled($state['value'] ?? '') ? ': '.$state['value'] : ''))
                                        : null
                                )
                                ->collapsed()
                                ->cloneable()
                                ->hint(__('admin.product.fields.attributes_hint'))
                                ->hintIcon('heroicon-o-magnifying-glass')
                                ->hintColor('info')
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->reorderableWithDragAndDrop()
                                ->addActionLabel(__('admin.product.actions.add_attribute'))
                                ->defaultItems(0)
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 7: Filters ────────────────────────────────────────
                    Tab::make(__('admin.product.tabs.filters'))
                        ->icon('heroicon-o-funnel')
                        ->schema(function () {
                            $groups = FilterGroup::active()
                                ->with('activeValues')
                                ->orderBy('sort_order')
                                ->get();

                            if ($groups->isEmpty()) {
                                return [
                                    Placeholder::make('no_filter_groups')
                                        ->label('')
                                        ->content('Chưa có filter group nào. Tạo tại Catalog → Filter Groups.'),
                                ];
                            }

                            return $groups->map(function (FilterGroup $group) {
                                $isColor = $group->type === FilterGroupType::Color;

                                // Group màu: option kèm ô swatch để admin tick đúng màu
                                // bằng mắt. allowHtml chỉ bật cho group màu — name phải
                                // tự escape vì Filament không escape nữa khi allowHtml.
                                $options = $group->activeValues->mapWithKeys(function ($v) use ($isColor) {
                                    $label = $v->name.($v->name_en ? " / {$v->name_en}" : '');

                                    if ($isColor) {
                                        $swatch = '<span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:'
                                            .e($v->color_hex ?: '#ffffff')
                                            .';border:1px solid rgba(0,0,0,.2);vertical-align:-2px;margin-right:6px"></span>';

                                        return [$v->id => $swatch.e($label)];
                                    }

                                    return [$v->id => $label];
                                })->toArray();

                                return Section::make($group->name.($group->name_en ? " / {$group->name_en}" : ''))
                                    ->compact()
                                    ->schema([
                                        Forms\Components\CheckboxList::make("filter_group_{$group->id}")
                                            ->label('')
                                            ->allowHtml($isColor)
                                            ->options($options)
                                            ->columns(3)
                                            ->columnSpanFull()
                                            ->gridDirection('row'),
                                    ]);
                            })->all();
                        }),

                    // ── Tab 8: Variants ───────────────────────────────────────
                    Tab::make(__('admin.product.tabs.variants'))
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([

                            // ── Step 1: Summary of variant-dimension values ───
                            Section::make(__('admin.product.sections.variant_step1'))
                                ->description(__('admin.product.sections.variant_step1_desc'))
                                ->icon('heroicon-o-tag')
                                ->schema([
                                    Placeholder::make('variant_dimensions_summary')
                                        ->label('')
                                        ->content(function ($livewire): HtmlString {
                                            $record = $livewire->record;

                                            if (! $record?->exists) {
                                                return new HtmlString(
                                                    '<em class="text-sm text-gray-400">Save the product first, then pick values in the Filters tab.</em>'
                                                );
                                            }

                                            $grouped = $record->variantDimensionValues()
                                                ->with('group')
                                                ->get()
                                                ->groupBy(fn ($v) => $v->group?->name ?? '—');

                                            if ($grouped->isEmpty()) {
                                                return new HtmlString(
                                                    '<em class="text-sm text-gray-400">Chưa chọn giá trị nào thuộc nhóm variant-dimension.</em>'
                                                );
                                            }

                                            return new HtmlString(
                                                $grouped->map(fn ($values, $groupName) => '<div style="margin-bottom:6px"><strong>'.e($groupName).':</strong> '
                                                    .e($values->pluck('name')->join(', ')).'</div>')
                                                    ->join('')
                                            );
                                        })
                                        ->columnSpanFull(),
                                ]),

                            // ── Generate button ───────────────────────────────
                            Actions::make([
                                Action::make('generate_variants')
                                    ->label(__('admin.product.actions.generate_variants'))
                                    // ->icon('heroicon-o-bolt')
                                    ->color('primary')
                                    ->requiresConfirmation()
                                    ->modalHeading(__('admin.product.actions.generate_variants_modal_heading'))
                                    ->modalDescription(__('admin.product.actions.generate_variants_modal_description'))
                                    ->modalSubmitActionLabel(__('admin.product.actions.generate_variants_submit'))
                                    ->action(function ($livewire): void {
                                        $product = $livewire->record;

                                        if (! $product?->exists) {
                                            Notification::make()
                                                ->title(__('admin.product.notifications.save_product_first_title'))
                                                ->body(__('admin.product.notifications.save_product_first_body'))
                                                ->warning()
                                                ->send();

                                            return;
                                        }

                                        $result = app(VariantGeneratorService::class)
                                            ->generate($product);

                                        if ($result['error']) {
                                            Notification::make()
                                                ->title(__('admin.product.notifications.cannot_generate_title'))
                                                ->body($result['error'])
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $body = $result['created'] > 0
                                            ? "{$result['created']} new variant(s) created."
                                            : 'All combinations already exist.';

                                        if ($result['skipped'] > 0) {
                                            $body .= " {$result['skipped']} skipped (already existed).";
                                        }

                                        Notification::make()
                                            ->title(__('admin.product.notifications.variants_generated_title'))
                                            ->body($body)
                                            ->success()
                                            ->send();

                                        // Redirect to the edit page so the form reloads with new variants
                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                    }),
                            ]),

                            // ── Step 2: Edit generated variants ───────────────
                            Section::make(__('admin.product.sections.variant_step2'))
                                ->description(__('admin.product.sections.variant_step2_desc'))
                                ->icon('heroicon-o-rectangle-stack')
                                ->schema([
                                    Forms\Components\Select::make('bulk_availability_status')
                                        ->label(__('admin.product.fields.bulk_availability_status'))
                                        ->options([
                                            VariantAvailability::Auto->value => 'Tự động (theo Stock)',
                                            VariantAvailability::OutOfStock->value => 'Hết hàng (ép)',
                                            VariantAvailability::PreOrder->value => 'Pre-order',
                                        ])
                                        ->native(false)
                                        ->dehydrated(false)
                                        ->helperText(__('admin.product.fields.bulk_availability_status_help'))
                                        ->suffixAction(
                                            Action::make('apply_bulk_availability')
                                                ->label(__('admin.product.actions.apply_bulk_availability'))
                                                ->icon('heroicon-o-check')
                                                ->action(function (Get $get, Set $set) {
                                                    $value = $get('bulk_availability_status');
                                                    if (blank($value)) {
                                                        return;
                                                    }

                                                    $variants = $get('variants') ?? [];
                                                    foreach ($variants as $key => $variant) {
                                                        $variants[$key]['availability_status'] = $value;
                                                    }
                                                    $set('variants', $variants);
                                                })
                                        )
                                        ->columnSpanFull(),

                                    Forms\Components\Repeater::make('variants')
                                        ->relationship(
                                            modifyQueryUsing: fn ($query) => $query
                                                ->with(['optionValues.group'])
                                                ->orderBy('sort_order'),
                                        )
                                        ->label('')
                                        ->schema([
                                            // ── Combination badge ──────────────
                                            Placeholder::make('combination_label')
                                                ->label(__('admin.product.fields.combination_label'))
                                                ->content(function ($record): HtmlString {
                                                    if (! $record?->exists) {
                                                        return new HtmlString(
                                                            '<em class="text-sm text-gray-400">New variant — combination assigned after generate</em>'
                                                        );
                                                    }

                                                    $record->loadMissing('optionValues.group');

                                                    $badges = $record->optionValues
                                                        ->sortBy(fn ($v) => $v->group?->sort_order ?? 0)
                                                        ->map(function ($v): string {
                                                            $isColor = $v->group?->type === FilterGroupType::Color;
                                                            $typeName = e($v->group?->name ?? '');
                                                            $val = e($v->name);

                                                            $swatch = $isColor
                                                                ? '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:'
                                                                    .e($v->color_hex ?: '#ffffff')
                                                                    .';border:1px solid rgba(0,0,0,.2);vertical-align:-1px;margin-right:4px"></span>'
                                                                : '';

                                                            return "<span style='display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:9999px;font-size:0.75rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;'>
                                                                        <span style='color:#93c5fd;font-size:0.65rem;'>{$typeName}</span>
                                                                        <strong>{$swatch}{$val}</strong>
                                                                    </span>";
                                                        })
                                                        ->join(' ');

                                                    return new HtmlString(
                                                        filled($badges)
                                                            ? $badges
                                                            : '<em class="text-sm text-gray-400">No combination assigned</em>'
                                                    );
                                                })
                                                ->columnSpanFull(),

                                            // ── Combination (editable) ─────────
                                            // Binds directly to ProductVariant::optionValues() — same
                                            // FilterValue rows used for facet filtering. Needed for
                                            // "+ Add variant manually" (no combination without this)
                                            // and to fix a wrong combination on an existing variant.
                                            Forms\Components\Select::make('optionValues')
                                                ->label(__('admin.product.fields.variant_combination'))
                                                ->relationship('optionValues', 'name')
                                                ->multiple()
                                                ->options(function (Get $get): array {
                                                    $productId = $get('../../id') ?? $get('product_id');
                                                    $product = $productId ? Product::find($productId) : null;

                                                    if (! $product) {
                                                        return [];
                                                    }

                                                    return $product->variantDimensionValues()
                                                        ->with('group')
                                                        ->get()
                                                        ->groupBy(fn ($v) => $v->group?->name ?? '—')
                                                        ->map(fn ($values) => $values->pluck('name', 'id')->all())
                                                        ->all();
                                                })
                                                ->rule(function (Get $get) {
                                                    return function (string $attribute, $value, Closure $fail) {
                                                        if (! is_array($value)) {
                                                            return;
                                                        }

                                                        $groupIds = FilterValue::whereIn('id', $value)->pluck('filter_group_id');

                                                        if ($groupIds->count() !== $groupIds->unique()->count()) {
                                                            $fail('Mỗi nhóm (Color, Size, ...) chỉ được chọn 1 giá trị.');
                                                        }
                                                    };
                                                })
                                                ->required()
                                                ->preload()
                                                ->native(false)
                                                ->helperText(__('admin.product.fields.variant_combination_help'))
                                                ->columnSpanFull(),

                                            // ── SKU ───────────────────────────
                                            Forms\Components\TextInput::make('sku')
                                                ->label(__('admin.product.fields.sku'))
                                                ->required()
                                                ->unique(table: 'product_variants', column: 'sku', ignoreRecord: true)
                                                ->columnSpan(1),

                                            // ── Image ─────────────────────────
                                            Forms\Components\Select::make('image_id')
                                                ->label(__('admin.product.fields.variant_image'))
                                                ->options(function (Get $get) {
                                                    $productId = $get('../../id') ?? $get('product_id');
                                                    if (! $productId) {
                                                        return [];
                                                    }

                                                    return ProductImage::where('product_id', $productId)
                                                        ->orderBy('sort_order')
                                                        ->get()
                                                        ->mapWithKeys(fn ($img) => [
                                                            $img->id => $img->alt_text
                                                                ? "#{$img->id} — {$img->alt_text}"
                                                                : "Image #{$img->id}",
                                                        ]);
                                                })
                                                ->nullable()
                                                ->native(false)
                                                ->placeholder(__('admin.product.fields.variant_image_placeholder'))
                                                ->columnSpan(1),

                                            // ── Pricing ───────────────────────
                                            Forms\Components\TextInput::make('price')
                                                ->label(__('admin.product.fields.price'))
                                                ->numeric()
                                                ->prefix('₫')
                                                ->required()
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('sale_price')
                                                ->label(__('admin.product.fields.sale_price'))
                                                ->numeric()
                                                ->prefix('₫')
                                                ->nullable()
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('price_usd')
                                                ->label(__('admin.product.fields.price_usd'))
                                                ->numeric()
                                                ->prefix('$')
                                                ->nullable()
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('sale_price_usd')
                                                ->label(__('admin.product.fields.sale_price_usd'))
                                                ->numeric()
                                                ->prefix('$')
                                                ->nullable()
                                                ->columnSpan(1),

                                            // ── Stock & Status ─────────────────
                                            Forms\Components\TextInput::make('stock_quantity')
                                                ->label(__('admin.product.fields.stock'))
                                                ->numeric()
                                                ->default(0)
                                                ->required()
                                                ->suffix(function ($state, Get $get) {
                                                    $override = $get('availability_status');

                                                    if ($override === VariantAvailability::PreOrder->value) {
                                                        return '🕐 Pre-order on Google';
                                                    }

                                                    if ($override === VariantAvailability::OutOfStock->value) {
                                                        return '⚠ OutOfStock (ép) on Google';
                                                    }

                                                    return (int) $state === 0
                                                        ? '⚠ OutOfStock on Google'
                                                        : 'in stock';
                                                })
                                                ->columnSpan(1),

                                            Forms\Components\Select::make('availability_status')
                                                ->label(__('admin.product.fields.availability_status'))
                                                ->options([
                                                    VariantAvailability::Auto->value => 'Tự động (theo Stock)',
                                                    VariantAvailability::OutOfStock->value => 'Hết hàng (ép)',
                                                    VariantAvailability::PreOrder->value => 'Pre-order',
                                                ])
                                                ->default(VariantAvailability::Auto->value)
                                                ->native(false)
                                                ->live()
                                                ->helperText(__('admin.product.fields.availability_status_help'))
                                                ->columnSpan(1),

                                            Forms\Components\Toggle::make('is_active')
                                                ->label(__('admin.product.fields.active'))
                                                ->default(true)
                                                ->inline(false)
                                                ->columnSpan(1),
                                        ])
                                        ->hint(__('admin.product.fields.variants_repeater_hint'))
                                        ->hintIcon('heroicon-o-magnifying-glass')
                                        ->hintColor('info')
                                        ->itemLabel(function (array $state): ?string {
                                            // Fallback label for collapsed items without loaded relation
                                            return filled($state['sku'] ?? '') ? $state['sku'] : null;
                                        })
                                        ->collapsed()
                                        ->orderColumn('sort_order')
                                        ->reorderable()
                                        ->reorderableWithDragAndDrop()
                                        ->addActionLabel(__('admin.product.actions.add_variant'))
                                        ->defaultItems(0)
                                        ->columns(2)
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'variants-repeater']),
                                ]),
                        ]),

                    // ── Tab 8: SEO ────────────────────────────────────────────
                    Tab::make(__('admin.product.tabs.seo'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Tabs::make('SeoLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.product.tabs.locale_vi'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaVi')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'vi', ...$data]
                                                )
                                                ->schema([
                                                    Section::make(__('admin.product.sections.meta_tags'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.meta_title_vi').'</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder(__('admin.product.fields.meta_title_placeholder'))
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText(__('admin.product.fields.meta_title_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->name) {
                                                                        $set('meta_title', $livewire->record->name);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('meta_description')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.meta_description_vi').'</span>'))
                                                                ->rows(3)
                                                                ->live(debounce: 400)
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 120, 160))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 120, 160))
                                                                ->helperText(__('admin.product.fields.meta_description_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $short = $livewire->record?->translation('vi')?->short_description;
                                                                        if ($short) {
                                                                            $set('meta_description', $short);
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('meta_keywords')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.meta_keywords_vi').'</span>'))
                                                                ->helperText(__('admin.product.fields.meta_keywords_help'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.canonical_url_vi').'</span>'))
                                                                ->url()
                                                                ->placeholder(__('admin.product.fields.canonical_url_vi_auto'))
                                                                ->hint(__('admin.product.fields.canonical_url_vi_auto'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $slug = $livewire->record?->translation('vi')?->slug ?? $livewire->record?->slug;
                                                                        if ($slug) {
                                                                            $set('canonical_url', LocaleUrl::for('product', $slug, 'vi'));
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('robots')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.robots').'</span>'))
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

                                                    Section::make(__('admin.product.sections.og_vi'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.og_title_vi').'</span>'))
                                                                ->placeholder(__('admin.product.fields.auto_from_meta_title_vi'))
                                                                ->hint(__('admin.product.fields.auto_from_meta_title_vi'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $set('og_title', $record?->meta_title
                                                                            ?? $livewire->record?->translation('vi')?->name
                                                                            ?? $livewire->record?->name);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('og_description')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.og_description_vi').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.product.fields.auto_from_meta_description_vi'))
                                                                ->hint(__('admin.product.fields.auto_from_meta_description_vi'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('og_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_image')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.og_image').'</span>'))
                                                                ->url()
                                                                ->placeholder(__('admin.product.fields.og_image_auto'))
                                                                ->hint(__('admin.product.fields.og_image_auto'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText(__('admin.product.fields.og_image_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $firstImage = $livewire->record?->images()->orderBy('sort_order')->first();
                                                                        if ($firstImage?->path) {
                                                                            $set('og_image', Storage::disk('public')->url($firstImage->path));
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('og_type')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.og_type').'</span>'))
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Product->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make(__('admin.product.sections.twitter_vi'))
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.twitter_card_type').'</span>'))
                                                                ->options([
                                                                    'summary' => 'Summary',
                                                                    'summary_large_image' => 'Summary Large Image',
                                                                ])
                                                                ->default('summary_large_image')
                                                                ->native(false),

                                                            Forms\Components\TextInput::make('twitter_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.twitter_title_vi').'</span>'))
                                                                ->placeholder(__('admin.product.fields.auto_from_meta_title_vi'))
                                                                ->hint(__('admin.product.fields.auto_from_meta_title_vi'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $set('twitter_title', $record?->meta_title
                                                                            ?? $livewire->record?->translation('vi')?->name
                                                                            ?? $livewire->record?->name);
                                                                    }
                                                                }),

                                                            Forms\Components\Textarea::make('twitter_description')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.twitter_description_vi').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.product.fields.auto_from_meta_description_vi'))
                                                                ->hint(__('admin.product.fields.auto_from_meta_description_vi'))
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

                                    Tab::make(__('admin.product.tabs.locale_en'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaEn')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'en', ...$data]
                                                )
                                                ->schema([
                                                    Section::make(__('admin.product.sections.meta_tags'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.meta_title_en').'</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder(__('admin.product.fields.meta_title_placeholder'))
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText(__('admin.product.fields.meta_title_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $name = $livewire->record?->translation('en')?->name ?? $livewire->record?->name;
                                                                        if ($name) {
                                                                            $set('meta_title', $name);
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('meta_description')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.meta_description_en').'</span>'))
                                                                ->rows(3)
                                                                ->live(debounce: 400)
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 120, 160))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 120, 160))
                                                                ->helperText(__('admin.product.fields.meta_description_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $short = $livewire->record?->translation('en')?->short_description;
                                                                        if ($short) {
                                                                            $set('meta_description', $short);
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('meta_keywords')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.meta_keywords_en').'</span>'))
                                                                ->helperText(__('admin.product.fields.meta_keywords_help'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.canonical_url_en').'</span>'))
                                                                ->url()
                                                                ->placeholder(__('admin.product.fields.canonical_url_en_auto'))
                                                                ->hint(__('admin.product.fields.canonical_url_en_auto'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $slug = $livewire->record?->translation('en')?->slug ?? $livewire->record?->slug;
                                                                        if ($slug) {
                                                                            $set('canonical_url', LocaleUrl::for('product', $slug, 'en'));
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('robots')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.robots').'</span>'))
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

                                                    Section::make(__('admin.product.sections.og_en'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.og_title_en').'</span>'))
                                                                ->placeholder(__('admin.product.fields.auto_from_meta_title_en'))
                                                                ->hint(__('admin.product.fields.auto_from_meta_title_en'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $set('og_title', $record?->meta_title
                                                                            ?? $livewire->record?->translation('en')?->name
                                                                            ?? $livewire->record?->name);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('og_description')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.og_description_en').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.product.fields.auto_from_meta_description_en'))
                                                                ->hint(__('admin.product.fields.auto_from_meta_description_en'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('og_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_image')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.og_image').'</span>'))
                                                                ->url()
                                                                ->placeholder(__('admin.product.fields.og_image_auto'))
                                                                ->hint(__('admin.product.fields.og_image_auto'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText(__('admin.product.fields.og_image_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $firstImage = $livewire->record?->images()->orderBy('sort_order')->first();
                                                                        if ($firstImage?->path) {
                                                                            $set('og_image', Storage::disk('public')->url($firstImage->path));
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('og_type')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.og_type').'</span>'))
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Product->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make(__('admin.product.sections.twitter_en'))
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.twitter_card_type').'</span>'))
                                                                ->options([
                                                                    'summary' => 'Summary',
                                                                    'summary_large_image' => 'Summary Large Image',
                                                                ])
                                                                ->default('summary_large_image')
                                                                ->native(false),

                                                            Forms\Components\TextInput::make('twitter_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.twitter_title_en').'</span>'))
                                                                ->placeholder(__('admin.product.fields.auto_from_meta_title_en'))
                                                                ->hint(__('admin.product.fields.auto_from_meta_title_en'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $set('twitter_title', $record?->meta_title
                                                                            ?? $livewire->record?->translation('en')?->name
                                                                            ?? $livewire->record?->name);
                                                                    }
                                                                }),

                                                            Forms\Components\Textarea::make('twitter_description')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.twitter_description_en').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.product.fields.auto_from_meta_description_en'))
                                                                ->hint(__('admin.product.fields.auto_from_meta_description_en'))
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
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 7: GEO / AI ───────────────────────────────────────
                    Tab::make(__('admin.product.tabs.geo_ai'))
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Tabs::make('GeoLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.product.tabs.locale_vi'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileVi')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'vi', ...$data]
                                                )
                                                ->schema([
                                                    Section::make(__('admin.product.sections.ai_context_vi'))
                                                        ->description(__('admin.product.sections.ai_context_desc'))
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.ai_summary_vi').'</span>'))
                                                                ->rows(4)
                                                                ->helperText(__('admin.product.fields.ai_summary_help'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.use_cases_vi').'</span>'))
                                                                ->rows(3)
                                                                ->placeholder(__('admin.product.fields.use_cases_vi_placeholder')),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.target_audience_vi').'</span>'))
                                                                ->maxLength(255)
                                                                ->placeholder(__('admin.product.fields.target_audience_vi_placeholder')),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.llm_context_hint_vi').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.product.fields.llm_context_hint_vi_placeholder'))
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->columns(2),

                                                    Section::make(__('admin.product.sections.key_facts_vi'))
                                                        ->description(__('admin.product.sections.key_facts_desc'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('key_facts')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('label')
                                                                        ->label(__('admin.product.fields.key_fact_label'))
                                                                        ->placeholder(__('admin.product.fields.key_fact_label_placeholder_vi'))
                                                                        ->required(),
                                                                    Forms\Components\TextInput::make('value')
                                                                        ->label(__('admin.product.fields.key_fact_value'))
                                                                        ->placeholder(__('admin.product.fields.key_fact_value_placeholder_vi'))
                                                                        ->required(),
                                                                ])
                                                                ->columns(2)
                                                                ->addActionLabel(__('admin.product.actions.add_key_fact'))
                                                                ->defaultItems(0)
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsed(),

                                                ]),
                                        ]),

                                    Tab::make(__('admin.product.tabs.locale_en'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileEn')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'en', ...$data]
                                                )
                                                ->schema([
                                                    Section::make(__('admin.product.sections.ai_context_en'))
                                                        ->description(__('admin.product.sections.ai_context_desc'))
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.ai_summary_en').'</span>'))
                                                                ->rows(4)
                                                                ->helperText(__('admin.product.fields.ai_summary_help'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.use_cases_en').'</span>'))
                                                                ->rows(3)
                                                                ->placeholder(__('admin.product.fields.use_cases_en_placeholder')),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.target_audience_en').'</span>'))
                                                                ->maxLength(255)
                                                                ->placeholder(__('admin.product.fields.target_audience_en_placeholder')),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.llm_context_hint_en').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.product.fields.llm_context_hint_en_placeholder'))
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->columns(2),

                                                    Section::make(__('admin.product.sections.key_facts_en'))
                                                        ->description(__('admin.product.sections.key_facts_desc'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('key_facts')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('label')
                                                                        ->label(__('admin.product.fields.key_fact_label'))
                                                                        ->placeholder(__('admin.product.fields.key_fact_label_placeholder_en'))
                                                                        ->required(),
                                                                    Forms\Components\TextInput::make('value')
                                                                        ->label(__('admin.product.fields.key_fact_value'))
                                                                        ->placeholder(__('admin.product.fields.key_fact_value_placeholder_en'))
                                                                        ->required(),
                                                                ])
                                                                ->columns(2)
                                                                ->addActionLabel(__('admin.product.actions.add_key_fact'))
                                                                ->defaultItems(0)
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsed(),

                                                ]),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 8: LLMs ───────────────────────────────────────────
                    Tab::make(__('admin.product.tabs.llms'))
                        ->icon('heroicon-o-document-text')
                        ->schema([

                            Section::make(__('admin.product.sections.llms_how_it_works'))
                                ->schema([
                                    Placeholder::make('llms_source_hint')
                                        ->label('')
                                        ->content(new HtmlString('
                                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                <li>Content is <strong>auto-assembled</strong> from the <strong>GEO / AI</strong> tab (ai_summary, key_facts, faq) when this product is saved.</li>
                                                <li>To change the output — edit the <strong>GEO / AI</strong> tab, not here.</li>
                                                <li>Use <strong>Regenerate</strong> below to force a re-sync without re-saving the product.</li>
                                                <li>Toggle <strong>Published</strong> to include / exclude from the AI document file.</li>
                                            </ul>
                                        '))
                                        ->columnSpanFull(),
                                ])
                                ->collapsed()
                                ->collapsible(),

                            Tabs::make('LlmsLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.product.tabs.locale_vi'))
                                        ->schema([
                                            Forms\Components\Repeater::make('llmsEntriesVi')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.llms_entries_vi').'</span>'))
                                                ->schema([
                                                    Placeholder::make('llms_preview')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.llms_preview').'</span>'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record) {
                                                                return new HtmlString('<em class="text-gray-400">Not generated yet — save the product to trigger sync.</em>');
                                                            }
                                                            $lines = [];
                                                            $lines[] = '## '.e($record->title);
                                                            $lines[] = 'URL: '.e($record->url);
                                                            if (filled($record->summary)) {
                                                                $lines[] = '';
                                                                $lines[] = 'Summary: '.e($record->summary);
                                                            }
                                                            if (filled($record->key_facts_text)) {
                                                                $lines[] = '';
                                                                $lines[] = 'Key Facts:';
                                                                $lines[] = e($record->key_facts_text);
                                                            }
                                                            if (filled($record->faq_text)) {
                                                                $lines[] = '';
                                                                $lines[] = 'FAQ:';
                                                                $lines[] = e($record->faq_text);
                                                            }

                                                            return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.8rem;line-height:1.6;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;color:#334155;">'.implode("\n", $lines).'</pre>');
                                                        })
                                                        ->columnSpanFull(),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.llms_published').'</span>'))
                                                        ->helperText(__('admin.product.fields.llms_published_help'))
                                                        ->inline(false),
                                                    Placeholder::make('updated_at')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.last_synced').'</span>'))
                                                        ->content(fn ($record) => $record?->updated_at
                                                            ? $record->updated_at->diffForHumans().' ('.$record->updated_at->format('d/m/Y H:i').')'
                                                            : '—'
                                                        ),
                                                ])
                                                ->addable(false)
                                                ->deletable(false)
                                                ->reorderable(false)
                                                ->defaultItems(0)
                                                ->columnSpanFull(),

                                            Actions::make([
                                                Action::make('regenerate_llms_vi')
                                                    ->label(__('admin.product.actions.regenerate_llms_vi'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading(__('admin.product.actions.regenerate_llms_vi_modal_heading'))
                                                    ->modalDescription(__('admin.product.actions.regenerate_llms_vi_modal_description'))
                                                    ->action(function ($livewire): void {
                                                        $product = $livewire->record;
                                                        if (! $product?->exists) {
                                                            return;
                                                        }
                                                        app(LlmsGeneratorService::class)->upsertEntry($product, null, 'vi');
                                                        Notification::make()->title(__('admin.product.notifications.llms_regenerated_vi'))->success()->send();
                                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                                    }),
                                            ]),
                                        ]),

                                    Tab::make(__('admin.product.tabs.locale_en'))
                                        ->schema([
                                            Forms\Components\Repeater::make('llmsEntriesEn')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.llms_entries_en').'</span>'))
                                                ->schema([
                                                    Placeholder::make('llms_preview')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.llms_preview').'</span>'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record) {
                                                                return new HtmlString('<em class="text-gray-400">Not generated yet — save the product to trigger sync.</em>');
                                                            }
                                                            $lines = [];
                                                            $lines[] = '## '.e($record->title);
                                                            $lines[] = 'URL: '.e($record->url);
                                                            if (filled($record->summary)) {
                                                                $lines[] = '';
                                                                $lines[] = 'Summary: '.e($record->summary);
                                                            }
                                                            if (filled($record->key_facts_text)) {
                                                                $lines[] = '';
                                                                $lines[] = 'Key Facts:';
                                                                $lines[] = e($record->key_facts_text);
                                                            }
                                                            if (filled($record->faq_text)) {
                                                                $lines[] = '';
                                                                $lines[] = 'FAQ:';
                                                                $lines[] = e($record->faq_text);
                                                            }

                                                            return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.8rem;line-height:1.6;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;color:#334155;">'.implode("\n", $lines).'</pre>');
                                                        })
                                                        ->columnSpanFull(),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.llms_published').'</span>'))
                                                        ->helperText(__('admin.product.fields.llms_published_help'))
                                                        ->inline(false),
                                                    Placeholder::make('updated_at')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.last_synced').'</span>'))
                                                        ->content(fn ($record) => $record?->updated_at
                                                            ? $record->updated_at->diffForHumans().' ('.$record->updated_at->format('d/m/Y H:i').')'
                                                            : '—'
                                                        ),
                                                ])
                                                ->addable(false)
                                                ->deletable(false)
                                                ->reorderable(false)
                                                ->defaultItems(0)
                                                ->columnSpanFull(),

                                            Actions::make([
                                                Action::make('regenerate_llms_en')
                                                    ->label(__('admin.product.actions.regenerate_llms_en'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading(__('admin.product.actions.regenerate_llms_en_modal_heading'))
                                                    ->modalDescription(__('admin.product.actions.regenerate_llms_en_modal_description'))
                                                    ->action(function ($livewire): void {
                                                        $product = $livewire->record;
                                                        if (! $product?->exists) {
                                                            return;
                                                        }
                                                        app(LlmsGeneratorService::class)->upsertEntry($product, null, 'en');
                                                        Notification::make()->title(__('admin.product.notifications.llms_regenerated_en'))->success()->send();
                                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                                    }),
                                            ]),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 9: JSON-LD ────────────────────────────────────────
                    Tab::make(__('admin.product.tabs.jsonld'))
                        ->icon('heroicon-o-code-bracket')
                        ->schema([

                            Section::make(__('admin.product.sections.jsonld_how_it_works'))
                                ->schema([
                                    Placeholder::make('jsonld_info')
                                        ->label('')
                                        ->content(new HtmlString('
                                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                <li>Schemas marked <strong>Auto</strong> are regenerated every time this product is saved — do not manually edit their payload here.</li>
                                                <li>To customize a payload, set <em>Auto Generated = off</em> first.</li>
                                                <li>Toggle <strong>Active</strong> to include / exclude a schema from the page <code>&lt;head&gt;</code>.</li>
                                            </ul>
                                        '))
                                        ->columnSpanFull(),
                                ])
                                ->collapsed()
                                ->collapsible(),

                            Tabs::make('JsonldLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.product.tabs.locale_vi'))
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasVi')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.jsonld_schemas_vi').'</span>'))
                                                ->schema([
                                                    Placeholder::make('schema_header')
                                                        ->label('')
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record) {
                                                                return new HtmlString('');
                                                            }
                                                            $type = $record->schema_type?->value ?? '—';
                                                            $label = e($record->label ?? '');
                                                            $auto = $record->is_auto_generated
                                                                ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#fef9c3;color:#854d0e;">⚡ Auto</span>'
                                                                : '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;">✎ Manual</span>';

                                                            return new HtmlString("<div style='display:flex;align-items:center;gap:10px;flex-wrap:wrap;'><span style='font-weight:700;font-size:0.95rem;color:#1e293b;'>{$type}</span>".(filled($label) ? "<span style='color:#64748b;font-size:0.85rem;'>— {$label}</span>" : '')."{$auto}</div>");
                                                        })
                                                        ->columnSpanFull(),
                                                    Placeholder::make('payload_preview')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.jsonld_payload_preview').'</span>'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">No payload yet — save the product to generate.</em>');
                                                            }
                                                            $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                                            return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">'.e($json).'</pre>');
                                                        })
                                                        ->columnSpanFull(),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.jsonld_active').'</span>'))
                                                        ->inline(false),
                                                    Placeholder::make('schema_updated_at')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.product.fields.jsonld_last_generated').'</span>'))
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

                                            Actions::make([
                                                Action::make('regenerate_jsonld_vi')
                                                    ->label(__('admin.product.actions.regenerate_jsonld_vi'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading(__('admin.product.actions.regenerate_jsonld_vi_modal_heading'))
                                                    ->modalDescription(__('admin.product.actions.regenerate_jsonld_vi_modal_description'))
                                                    ->action(function ($livewire): void {
                                                        $product = $livewire->record;
                                                        if (! $product?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($product, 'vi');
                                                        Notification::make()->title(__('admin.product.notifications.jsonld_regenerated_vi'))->success()->send();
                                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                                    }),
                                            ]),
                                        ]),

                                    Tab::make(__('admin.product.tabs.locale_en'))
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasEn')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.jsonld_schemas_en').'</span>'))
                                                ->schema([
                                                    Placeholder::make('schema_header')
                                                        ->label('')
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record) {
                                                                return new HtmlString('');
                                                            }
                                                            $type = $record->schema_type?->value ?? '—';
                                                            $label = e($record->label ?? '');
                                                            $auto = $record->is_auto_generated
                                                                ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#fef9c3;color:#854d0e;">⚡ Auto</span>'
                                                                : '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;">✎ Manual</span>';

                                                            return new HtmlString("<div style='display:flex;align-items:center;gap:10px;flex-wrap:wrap;'><span style='font-weight:700;font-size:0.95rem;color:#1e293b;'>{$type}</span>".(filled($label) ? "<span style='color:#64748b;font-size:0.85rem;'>— {$label}</span>" : '')."{$auto}</div>");
                                                        })
                                                        ->columnSpanFull(),
                                                    Placeholder::make('payload_preview')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.jsonld_payload_preview').'</span>'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">No payload yet — save the product to generate.</em>');
                                                            }
                                                            $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                                            return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">'.e($json).'</pre>');
                                                        })
                                                        ->columnSpanFull(),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.jsonld_active').'</span>'))
                                                        ->inline(false),
                                                    Placeholder::make('schema_updated_at')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.product.fields.jsonld_last_generated').'</span>'))
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

                                            Actions::make([
                                                Action::make('regenerate_jsonld_en')
                                                    ->label(__('admin.product.actions.regenerate_jsonld_en'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading(__('admin.product.actions.regenerate_jsonld_en_modal_heading'))
                                                    ->modalDescription(__('admin.product.actions.regenerate_jsonld_en_modal_description'))
                                                    ->action(function ($livewire): void {
                                                        $product = $livewire->record;
                                                        if (! $product?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($product, 'en');
                                                        Notification::make()->title(__('admin.product.notifications.jsonld_regenerated_en'))->success()->send();
                                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                                    }),
                                            ]),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab: Translations ─────────────────────────────────────
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['thumbnail', 'categories']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail.path')
                    ->label(__('admin.product.fields.thumbnail'))
                    ->disk('public'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label(__('admin.product.fields.sku'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('categories.name')
                    ->label(__('admin.product.fields.categories'))
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('price')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('VND')
                    ->placeholder(__('admin.product.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label(__('admin.product.fields.stock'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.product.fields.active')),

                Tables\Filters\SelectFilter::make('categories')
                    ->label(__('admin.product.fields.category'))
                    ->relationship('categories', 'name')
                    ->multiple(),

                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Product $record) => $record->is_active ? __('admin.product.actions.hide') : __('admin.product.actions.show'))
                    ->icon(fn (Product $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Product $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (Product $record) => $record->update(['is_active' => ! $record->is_active])),

                Action::make('audit')
                    ->label(__('admin.product.actions.audit'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->action(function (Product $record): StreamedResponse {
                        $content = app(ProductAuditService::class)->buildReport($record);
                        $filename = $record->slug.'-audit-'.now()->format('Ymd-Hi').'.md';

                        return response()->streamDownload(
                            fn () => print ($content),
                            $filename,
                            ['Content-Type' => 'text/markdown; charset=utf-8'],
                        );
                    }),

                RestoreAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(SoftDeletingScope::class);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    // ── Slug validation rules ─────────────────────────────────────────────────

    /**
     * Unique (locale, slug) trên product_translations — bỏ qua row của chính product đang edit.
     */
    private static function uniqueTranslationSlugRule(string $locale, ?Product $record): Unique
    {
        $rule = (new Unique('product_translations', 'slug'))->where('locale', $locale);

        if ($record) {
            $rule->ignore($record->id, 'product_id');
        }

        return $rule;
    }

    /**
     * Unique trên products.slug (bao gồm cả soft-deleted vì DB unique index không loại trừ).
     */
    private static function uniqueProductSlugRule(?Product $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $exists = Product::withTrashed()
                ->where('slug', $value)
                ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                ->exists();

            if ($exists) {
                $fail('Slug này đã được dùng bởi sản phẩm khác (kể cả sản phẩm đã xóa mềm).');
            }
        };
    }

    // ── SEO char counter helpers ──────────────────────────────────────────────

    /**
     * Live hint text: "76 / 70 chars — 6 over" or "52 / 70 chars ✓"
     */
    private static function charCounter(?string $state, int $min, int $max): string
    {
        $len = mb_strlen($state ?? '');

        return "{$len} / {$min}–{$max} chars";
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
