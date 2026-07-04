<?php

namespace App\Filament\Resources;

use App\Enums\FilterGroupType;
use App\Enums\OgType;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\FilterGroup;
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

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->tabs([

                    // ── Tab 1: General ────────────────────────────────────────
                    Tab::make('General')
                        ->schema([
                            Forms\Components\Select::make('categories')
                                ->label('Categories')
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
                                ->label('Primary Category')
                                ->helperText('Dùng cho breadcrumb JSON-LD. Chọn sau khi đã chọn Categories.')
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
                                ->label('Brand')
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false),

                            Forms\Components\Select::make('manufacturer_id')
                                ->label('Manufacturer')
                                ->relationship('manufacturer', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false),

                            Forms\Components\TextInput::make('sku')
                                ->label('SKU')
                                ->required(fn (Get $get): bool => (bool) $get('is_active'))
                                ->unique(table: Product::class, column: 'sku', ignoreRecord: true),

                            Forms\Components\Toggle::make('is_active')
                                ->default(true),

                            Forms\Components\Toggle::make('show_price')
                                ->label('Hiển thị giá trên website')
                                ->helperText('Tắt → ẩn toàn bộ giá, hiện nút "Liên hệ báo giá".')
                                ->default(true),
                        ])
                        ->columns(2),

                    // ── Tab 2: Content ────────────────────────────────────────
                    Tab::make('Content')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Tabs::make('LocaleTabs')
                                ->tabs([
                                    Tab::make('🇻🇳 Tiếng Việt (vi)')
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.vi.name')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Tên sản phẩm (vi)</span>'))
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $set('translations.vi.slug', Str::slug($state ?? ''));
                                                    $set('name', $state);
                                                    $set('slug', Str::slug($state ?? ''));
                                                })
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.vi.slug')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Slug (vi)</span>'))
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('slug', $state))
                                                ->helperText('Auto-generated from name. Must be unique per locale.')
                                                ->rules([
                                                    fn (?Product $record) => self::uniqueTranslationSlugRule('vi', $record),
                                                    fn (?Product $record) => self::uniqueProductSlugRule($record),
                                                ])
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.vi.short_description')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Mô tả ngắn (vi)</span>'))
                                                ->rows(3)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('short_description', $state))
                                                ->columnSpanFull(),

                                            Forms\Components\RichEditor::make('translations.vi.description')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Mô tả đầy đủ (vi)</span>'))
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2),

                                    Tab::make('🇬🇧 English (en)')
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.en.name')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Product name (en)</span>'))
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('translations.en.slug', Str::slug($state ?? '')))
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.en.slug')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Slug (en)</span>'))
                                                ->helperText('Auto-generated from name. Must be unique per locale.')
                                                ->requiredWith('translations.en.name')
                                                ->rules([
                                                    fn (?Product $record) => self::uniqueTranslationSlugRule('en', $record),
                                                ])
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.en.short_description')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Short description (en)</span>'))
                                                ->rows(3)
                                                ->columnSpanFull(),

                                            Forms\Components\RichEditor::make('translations.en.description')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Description (en)</span>'))
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 3: Pricing & Stock ────────────────────────────────
                    Tab::make('Pricing & Stock')
                        ->schema([

                            Section::make('🇻🇳 Giá Việt Nam')
                                ->schema([
                                    Forms\Components\Select::make('translations.vi.currency')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Đơn vị tiền (vi)</span>'))
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
                                        ->hint('Dùng trong JSON-LD schema (priceCurrency)')
                                        ->hintIcon('heroicon-o-code-bracket')
                                        ->hintColor('info')
                                        ->afterStateUpdated(fn ($state, Set $set) => $set('currency', $state))
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('translations.vi.price')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Giá (vi)</span>'))
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
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Giá khuyến mãi (vi)</span>'))
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
                                        ->label('Hiển thị giá gốc bị gạch')
                                        ->helperText('Bật → hiện giá cũ gạch ngang bên cạnh giá khuyến mãi. Tắt → chỉ hiện giá KM.')
                                        ->default(true)
                                        ->inline(false)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Section::make('🇬🇧 English Pricing')
                                ->schema([
                                    Forms\Components\Select::make('translations.en.currency')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Currency (en)</span>'))
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
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Price (en)</span>'))
                                        ->numeric()
                                        ->prefix(fn ($get) => match ($get('translations.en.currency')) {
                                            'EUR' => '€',
                                            'JPY', 'KRW', 'CNY' => '¥',
                                            'SGD' => 'S$', 'THB' => '฿',
                                            'VND' => '₫',
                                            default => '$',
                                        }),

                                    Forms\Components\TextInput::make('translations.en.sale_price')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Sale Price (en)</span>'))
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

                            Section::make('Stock')
                                ->schema([
                                    Forms\Components\TextInput::make('stock_quantity')
                                        ->label('Stock Quantity')
                                        ->numeric()
                                        ->default(0)
                                        ->required(fn (Get $get): bool => (bool) $get('is_active'))
                                        ->columnSpanFull(),
                                ]),

                        ]),

                    // ── Tab 3: Images ─────────────────────────────────────────
                    Tab::make('Images')
                        ->schema([
                            Forms\Components\Repeater::make('images')
                                ->relationship()
                                ->schema([
                                    Forms\Components\FileUpload::make('path')
                                        ->label('Image')
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
                                        ->hint('Filenames are auto-converted to accent-free Latin; duplicates get a numeric suffix (e.g., quan-tay-1.jpg).')
                                        ->hintIcon('heroicon-o-information-circle')
                                        ->hintColor('success')
                                        ->image()
                                        ->imagePreviewHeight('120')
                                        ->imageEditor()
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('alt_text')
                                        ->label('Alt Text')
                                        ->columnSpan(1),

                                    Forms\Components\Checkbox::make('is_card_priority')
                                        ->label('Ưu tiên hiển thị trên Product Card')
                                        ->helperText('Chọn tối đa 2 ảnh. Ảnh có thứ tự (index) nhỏ hơn sẽ là ảnh chính, ảnh còn lại là ảnh hover.')
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
                                                    ->title('Chỉ được chọn tối đa 2 ảnh ưu tiên')
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
                    Tab::make('Videos')
                        ->schema([
                            Forms\Components\Repeater::make('videos')
                                ->relationship()
                                ->schema([
                                    // ── Files ─────────────────────────────────
                                    Forms\Components\FileUpload::make('path')
                                        ->label('Video File')
                                        ->disk('public')
                                        ->directory(fn () => 'products/'.now()->format('Y/m'))
                                        ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\FileUpload::make('thumbnail_path')
                                        ->label('Thumbnail')
                                        ->disk('public')
                                        ->directory(fn () => 'products/'.now()->format('Y/m'))
                                        ->image()
                                        ->imagePreviewHeight('100')
                                        ->columnSpan(1),

                                    // ── SEO fields ────────────────────────────
                                    Forms\Components\TextInput::make('title')
                                        ->label('Title')
                                        ->maxLength(255)
                                        ->hint('Required for VideoObject rich results')
                                        ->hintIcon('heroicon-o-magnifying-glass')
                                        ->hintColor('warning')
                                        ->columnSpan(2),

                                    Forms\Components\Textarea::make('description')
                                        ->label('Description')
                                        ->rows(2)
                                        ->hint('Required for VideoObject rich results')
                                        ->hintIcon('heroicon-o-magnifying-glass')
                                        ->hintColor('warning')
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('duration')
                                        ->label('Duration (ISO 8601)')
                                        ->placeholder('PT2M30S')
                                        ->hint('e.g. PT30S = 30s, PT2M30S = 2m30s, PT1H = 1h')
                                        ->hintIcon('heroicon-o-clock')
                                        ->hintColor('info')
                                        ->columnSpan(2),
                                ])
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->reorderableWithDragAndDrop()
                                ->defaultItems(0)
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 6: Attributes ─────────────────────────────────────
                    Tab::make('Attributes')
                        ->icon('heroicon-o-list-bullet')
                        ->schema([
                            Forms\Components\Repeater::make('attributes')
                                ->relationship()
                                ->label('')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 Thuộc tính</span>'))
                                        ->placeholder('vd: Vật liệu, Khối lượng, Điện áp')
                                        ->required()
                                        ->live(debounce: 300)
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('name_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 Attribute</span>'))
                                        ->placeholder('e.g. Material, Weight, Voltage')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('value')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 Giá trị</span>'))
                                        ->placeholder('vd: Nhôm, 500g, 220V')
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('value_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 Value</span>'))
                                        ->placeholder('e.g. Aluminum, 500g, 220V')
                                        ->columnSpan(1),
                                ])
                                ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? '')
                                        ? ($state['name'].(filled($state['value'] ?? '') ? ': '.$state['value'] : ''))
                                        : null
                                )
                                ->collapsed()
                                ->cloneable()
                                ->hint('Used in Product JSON-LD as additionalProperty (PropertyValue). Helps Google understand product specs.')
                                ->hintIcon('heroicon-o-magnifying-glass')
                                ->hintColor('info')
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->reorderableWithDragAndDrop()
                                ->addActionLabel('+ Add attribute')
                                ->defaultItems(0)
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 7: Filters ────────────────────────────────────────
                    Tab::make('Filters')
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
                    Tab::make('Variants')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([

                            // ── Step 1: Define option types & values ──────────
                            Section::make('Step 1 — Define Options')
                                ->description('Add option types (e.g. Color, Size) and their possible values. Then click Generate.')
                                ->icon('heroicon-o-tag')
                                ->schema([
                                    Forms\Components\Repeater::make('optionTypes')
                                        ->relationship()
                                        ->label('')
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Option Name')
                                                ->placeholder('e.g. Color, Size, Storage, Material')
                                                ->required()
                                                ->distinct()
                                                ->columnSpan(1),

                                            Forms\Components\Repeater::make('values')
                                                ->relationship()
                                                ->label('Values')
                                                ->schema([
                                                    Forms\Components\TextInput::make('value')
                                                        ->label('')
                                                        ->placeholder('e.g. Red')
                                                        ->required(),
                                                ])
                                                ->orderColumn('sort_order')
                                                ->reorderable()
                                                ->reorderableWithDragAndDrop()
                                                ->addActionLabel('+ Add value')
                                                ->defaultItems(1)
                                                ->columns(1)
                                                ->columnSpan(3),
                                        ])
                                        ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? '')
                                                ? '⚙ '.$state['name']
                                                : null
                                        )
                                        ->collapsed()
                                        ->orderColumn('sort_order')
                                        ->reorderable()
                                        ->addActionLabel('+ Add option')
                                        ->defaultItems(0)
                                        ->columns(4)
                                        ->columnSpanFull(),
                                ]),

                            // ── Generate button ───────────────────────────────
                            Actions::make([
                                Action::make('generate_variants')
                                    ->label('Generate Combinations')
                                    // ->icon('heroicon-o-bolt')
                                    ->color('primary')
                                    ->requiresConfirmation()
                                    ->modalHeading('Generate Variant Combinations')
                                    ->modalDescription('This will create all missing combinations from your option types. Existing variants are never modified or deleted.')
                                    ->modalSubmitActionLabel('Generate')
                                    ->action(function ($livewire): void {
                                        $product = $livewire->record;

                                        if (! $product?->exists) {
                                            Notification::make()
                                                ->title('Save the product first')
                                                ->body('Please save the product before generating variant combinations.')
                                                ->warning()
                                                ->send();

                                            return;
                                        }

                                        $result = app(VariantGeneratorService::class)
                                            ->generate($product);

                                        if ($result['error']) {
                                            Notification::make()
                                                ->title('Cannot generate')
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
                                            ->title('Variants generated')
                                            ->body($body)
                                            ->success()
                                            ->send();

                                        // Redirect to the edit page so the form reloads with new variants
                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                    }),
                            ]),

                            // ── Step 2: Edit generated variants ───────────────
                            Section::make('Step 2 — Manage Variants')
                                ->description('Fill in SKU, price and stock for each generated combination. Stock = 0 → "OutOfStock" on Google.')
                                ->icon('heroicon-o-rectangle-stack')
                                ->schema([
                                    Forms\Components\Repeater::make('variants')
                                        ->relationship(
                                            modifyQueryUsing: fn ($query) => $query
                                                ->with(['optionValues.optionType'])
                                                ->orderBy('sort_order'),
                                        )
                                        ->label('')
                                        ->schema([
                                            // ── Combination badge ──────────────
                                            Placeholder::make('combination_label')
                                                ->label('Combination')
                                                ->content(function ($record): HtmlString {
                                                    if (! $record?->exists) {
                                                        return new HtmlString(
                                                            '<em class="text-sm text-gray-400">New variant — combination assigned after generate</em>'
                                                        );
                                                    }

                                                    $record->loadMissing('optionValues.optionType');

                                                    $label = $record->optionValues
                                                        ->sortBy(fn ($v) => $v->optionType?->sort_order ?? 0)
                                                        ->pluck('value')
                                                        ->join(' / ');

                                                    $badges = $record->optionValues
                                                        ->sortBy(fn ($v) => $v->optionType?->sort_order ?? 0)
                                                        ->map(function ($v): string {
                                                            $typeName = e($v->optionType?->name ?? '');
                                                            $val = e($v->value);

                                                            return "<span style='display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:9999px;font-size:0.75rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;'>
                                                                        <span style='color:#93c5fd;font-size:0.65rem;'>{$typeName}</span>
                                                                        <strong>{$val}</strong>
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

                                            // ── SKU ───────────────────────────
                                            Forms\Components\TextInput::make('sku')
                                                ->label('SKU')
                                                ->required()
                                                ->unique(table: 'product_variants', column: 'sku', ignoreRecord: true)
                                                ->columnSpan(1),

                                            // ── Image ─────────────────────────
                                            Forms\Components\Select::make('image_id')
                                                ->label('Variant Image')
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
                                                ->placeholder('— same as product —')
                                                ->columnSpan(1),

                                            // ── Pricing ───────────────────────
                                            Forms\Components\TextInput::make('price')
                                                ->label('Price')
                                                ->numeric()
                                                ->prefix('₫')
                                                ->required()
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('sale_price')
                                                ->label('Sale Price')
                                                ->numeric()
                                                ->prefix('₫')
                                                ->nullable()
                                                ->columnSpan(1),

                                            // ── Stock & Status ─────────────────
                                            Forms\Components\TextInput::make('stock_quantity')
                                                ->label('Stock')
                                                ->numeric()
                                                ->default(0)
                                                ->required()
                                                ->suffix(fn ($state) => (int) $state === 0
                                                    ? '⚠ OutOfStock on Google'
                                                    : 'in stock')
                                                ->columnSpan(1),

                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true)
                                                ->inline(false)
                                                ->columnSpan(1),
                                        ])
                                        ->hint('Each variant = one Offer in Product JSON-LD. Stock = 0 → OutOfStock on Google.')
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
                                        ->addActionLabel('+ Add variant manually')
                                        ->defaultItems(0)
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ── Tab 8: SEO ────────────────────────────────────────────
                    Tab::make('SEO')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Tabs::make('SeoLocaleTabs')
                                ->tabs([
                                    Tab::make('🇻🇳 Tiếng Việt')
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaVi')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'vi', ...$data]
                                                )
                                                ->schema([
                                                    Section::make('Meta Tags')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Meta Title (vi)</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder('Tự điền từ tên sản phẩm')
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText('Tối ưu: 50–70 ký tự.')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->name) {
                                                                        $set('meta_title', $livewire->record->name);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('meta_description')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Meta Description (vi)</span>'))
                                                                ->rows(3)
                                                                ->live(debounce: 400)
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 120, 160))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 120, 160))
                                                                ->helperText('Tối ưu: 120–160 ký tự.')
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Meta Keywords (vi)</span>'))
                                                                ->helperText('Phân cách bằng dấu phẩy')
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Canonical URL (vi)</span>'))
                                                                ->url()
                                                                ->placeholder('Tự tạo từ slug (vi)')
                                                                ->hint('Tự tạo từ slug (vi)')
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Robots</span>'))
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

                                                    Section::make('Open Graph (vi)')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">OG Title (vi)</span>'))
                                                                ->placeholder('Tự điền từ Meta Title (vi)')
                                                                ->hint('Tự điền từ Meta Title (vi)')
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">OG Description (vi)</span>'))
                                                                ->rows(2)
                                                                ->placeholder('Tự điền từ Meta Description (vi)')
                                                                ->hint('Tự điền từ Meta Description (vi)')
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('og_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_image')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">OG Image URL</span>'))
                                                                ->url()
                                                                ->placeholder('Tự điền từ ảnh đầu tiên')
                                                                ->hint('Tự điền từ ảnh đầu tiên')
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText('Khuyến nghị: 1200×630px.')
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">OG Type</span>'))
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Product->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make('Twitter Card (vi)')
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Card Type</span>'))
                                                                ->options([
                                                                    'summary' => 'Summary',
                                                                    'summary_large_image' => 'Summary Large Image',
                                                                ])
                                                                ->default('summary_large_image')
                                                                ->native(false),

                                                            Forms\Components\TextInput::make('twitter_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Twitter Title (vi)</span>'))
                                                                ->placeholder('Tự điền từ Meta Title (vi)')
                                                                ->hint('Tự điền từ Meta Title (vi)')
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Twitter Description (vi)</span>'))
                                                                ->rows(2)
                                                                ->placeholder('Tự điền từ Meta Description (vi)')
                                                                ->hint('Tự điền từ Meta Description (vi)')
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

                                    Tab::make('🇬🇧 English')
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaEn')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'en', ...$data]
                                                )
                                                ->schema([
                                                    Section::make('Meta Tags')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Meta Title (en)</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder('Auto-filled from product name')
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText('Optimal: 50–70 chars.')
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Meta Description (en)</span>'))
                                                                ->rows(3)
                                                                ->live(debounce: 400)
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 120, 160))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 120, 160))
                                                                ->helperText('Optimal: 120–160 chars.')
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Meta Keywords (en)</span>'))
                                                                ->helperText('Comma separated')
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Canonical URL (en)</span>'))
                                                                ->url()
                                                                ->placeholder('Auto-generated from slug')
                                                                ->hint('Auto-generated from slug')
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Robots</span>'))
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

                                                    Section::make('Open Graph (en)')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">OG Title (en)</span>'))
                                                                ->placeholder('Auto-filled from Meta Title (en)')
                                                                ->hint('Auto-filled from Meta Title (en)')
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">OG Description (en)</span>'))
                                                                ->rows(2)
                                                                ->placeholder('Auto-filled from Meta Description (en)')
                                                                ->hint('Auto-filled from Meta Description (en)')
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('og_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_image')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">OG Image URL</span>'))
                                                                ->url()
                                                                ->placeholder('Auto-filled from first product image')
                                                                ->hint('Auto-filled from first product image')
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText('Recommended: 1200×630px.')
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">OG Type</span>'))
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Product->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make('Twitter Card (en)')
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Card Type</span>'))
                                                                ->options([
                                                                    'summary' => 'Summary',
                                                                    'summary_large_image' => 'Summary Large Image',
                                                                ])
                                                                ->default('summary_large_image')
                                                                ->native(false),

                                                            Forms\Components\TextInput::make('twitter_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Twitter Title (en)</span>'))
                                                                ->placeholder('Auto-filled from Meta Title (en)')
                                                                ->hint('Auto-filled from Meta Title (en)')
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Twitter Description (en)</span>'))
                                                                ->rows(2)
                                                                ->placeholder('Auto-filled from Meta Description (en)')
                                                                ->hint('Auto-filled from Meta Description (en)')
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
                    Tab::make('GEO / AI')
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Tabs::make('GeoLocaleTabs')
                                ->tabs([
                                    Tab::make('🇻🇳 Tiếng Việt')
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileVi')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'vi', ...$data]
                                                )
                                                ->schema([
                                                    Section::make('AI Context (vi)')
                                                        ->description('Dùng bởi ChatGPT, Gemini, Perplexity khi trả lời về sản phẩm này.')
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">AI Summary (vi)</span>'))
                                                                ->rows(4)
                                                                ->helperText('2–4 câu mô tả sản phẩm cho AI. Hiển thị đầu tiên trong llms.txt.')
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Use Cases (vi)</span>'))
                                                                ->rows(3)
                                                                ->placeholder('vd: Chiếu sáng nội thất, trưng bày bảo tàng, kệ bán lẻ'),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Target Audience (vi)</span>'))
                                                                ->maxLength(255)
                                                                ->placeholder('vd: Nhà thiết kế chiếu sáng, nhà thầu điện'),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">LLM Context Hint (vi)</span>'))
                                                                ->rows(2)
                                                                ->placeholder('vd: Cạnh tranh với Philips Hue, đạt CE/RoHS, không chống nước')
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->columns(2),

                                                    Section::make('Key Facts (vi)')
                                                        ->description('Thông tin cấu trúc cho AI — chứng nhận, tiêu chuẩn, điểm bán hàng.')
                                                        ->schema([
                                                            Forms\Components\KeyValue::make('key_facts')
                                                                ->label('')
                                                                ->keyLabel('Thông tin')
                                                                ->valueLabel('Giá trị')
                                                                ->keyPlaceholder('vd: Chứng nhận')
                                                                ->valuePlaceholder('vd: CE / RoHS')
                                                                ->addActionLabel('+ Thêm thông tin')
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsed(),

                                                ]),

                                            Section::make('FAQ (vi)')
                                                ->description('Câu hỏi thường gặp — đưa vào JSON-LD FAQPage và llms.txt.')
                                                ->schema([
                                                    Forms\Components\Repeater::make('faq_items_vi')
                                                        ->label('')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('question')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Câu hỏi</span>'))
                                                                ->required()
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('answer')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Trả lời</span>'))
                                                                ->rows(2)
                                                                ->required()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->afterStateHydrated(function (Forms\Components\Repeater $component, $record): void {
                                                            $component->state($record?->faq_items_vi ?? []);
                                                        })
                                                        ->maxItems(10)
                                                        ->defaultItems(0)
                                                        ->addActionLabel('+ Thêm Q&A')
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsed(),
                                        ]),

                                    Tab::make('🇬🇧 English')
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileEn')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'en', ...$data]
                                                )
                                                ->schema([
                                                    Section::make('AI Context (en)')
                                                        ->description('Used by ChatGPT, Gemini, Perplexity when answering questions about this product.')
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">AI Summary (en)</span>'))
                                                                ->rows(4)
                                                                ->helperText('2–4 sentences describing this product for AI engines. Shown first in llms.txt.')
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Use Cases (en)</span>'))
                                                                ->rows(3)
                                                                ->placeholder('e.g. Indoor accent lighting, museum displays, retail shelving'),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Target Audience (en)</span>'))
                                                                ->maxLength(255)
                                                                ->placeholder('e.g. Lighting designers, electrical contractors'),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">LLM Context Hint (en)</span>'))
                                                                ->rows(2)
                                                                ->placeholder('e.g. Competes with Philips Hue, CE/RoHS certified, not waterproof')
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->columns(2),

                                                    Section::make('Key Facts (en)')
                                                        ->description('Structured facts for AI engines — certifications, compliance, key selling points.')
                                                        ->schema([
                                                            Forms\Components\KeyValue::make('key_facts')
                                                                ->label('')
                                                                ->keyLabel('Fact')
                                                                ->valueLabel('Value')
                                                                ->keyPlaceholder('e.g. Certification')
                                                                ->valuePlaceholder('e.g. CE / RoHS')
                                                                ->addActionLabel('+ Add fact')
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsed(),

                                                ]),

                                            Section::make('FAQ (en)')
                                                ->description('Frequently asked questions — injected into JSON-LD FAQPage schema and llms.txt.')
                                                ->schema([
                                                    Forms\Components\Repeater::make('faq_items_en')
                                                        ->label('')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('question')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Question</span>'))
                                                                ->required()
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('answer')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Answer</span>'))
                                                                ->rows(2)
                                                                ->required()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->afterStateHydrated(function (Forms\Components\Repeater $component, $record): void {
                                                            $component->state($record?->faq_items_en ?? []);
                                                        })
                                                        ->maxItems(10)
                                                        ->defaultItems(0)
                                                        ->addActionLabel('+ Add Q&A')
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsed(),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 8: LLMs ───────────────────────────────────────────
                    Tab::make('LLMs')
                        ->icon('heroicon-o-document-text')
                        ->schema([

                            Section::make('How LLMs entries work')
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
                                    Tab::make('🇻🇳 Tiếng Việt')
                                        ->schema([
                                            Forms\Components\Repeater::make('llmsEntriesVi')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Entries (vi)</span>'))
                                                ->schema([
                                                    Placeholder::make('llms_preview')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Preview (llms.txt output)</span>'))
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
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Published to llms.txt</span>'))
                                                        ->helperText('Toggle off to exclude this entry from the AI document.')
                                                        ->inline(false),
                                                    Placeholder::make('updated_at')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Last synced</span>'))
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
                                                    ->label('Regenerate vi')
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Regenerate LLMs Entry (vi)')
                                                    ->modalDescription('This will re-pull data from GEO/AI (vi) tab and overwrite the current entry. Proceed?')
                                                    ->action(function ($livewire): void {
                                                        $product = $livewire->record;
                                                        if (! $product?->exists) {
                                                            return;
                                                        }
                                                        app(LlmsGeneratorService::class)->upsertEntry($product, null, 'vi');
                                                        Notification::make()->title('LLMs entry (vi) regenerated')->success()->send();
                                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                                    }),
                                            ]),
                                        ]),

                                    Tab::make('🇬🇧 English')
                                        ->schema([
                                            Forms\Components\Repeater::make('llmsEntriesEn')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Entries (en)</span>'))
                                                ->schema([
                                                    Placeholder::make('llms_preview')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Preview (llms.txt output)</span>'))
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
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Published to llms.txt</span>'))
                                                        ->helperText('Toggle off to exclude this entry from the AI document.')
                                                        ->inline(false),
                                                    Placeholder::make('updated_at')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Last synced</span>'))
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
                                                    ->label('Regenerate en')
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Regenerate LLMs Entry (en)')
                                                    ->modalDescription('This will re-pull data from GEO/AI (en) tab and overwrite the current entry. Proceed?')
                                                    ->action(function ($livewire): void {
                                                        $product = $livewire->record;
                                                        if (! $product?->exists) {
                                                            return;
                                                        }
                                                        app(LlmsGeneratorService::class)->upsertEntry($product, null, 'en');
                                                        Notification::make()->title('LLMs entry (en) regenerated')->success()->send();
                                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                                    }),
                                            ]),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 9: JSON-LD ────────────────────────────────────────
                    Tab::make('JSON-LD')
                        ->icon('heroicon-o-code-bracket')
                        ->schema([

                            Section::make('How JSON-LD schemas work')
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
                                    Tab::make('🇻🇳 Tiếng Việt')
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasVi')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Schemas (vi)</span>'))
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
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Payload (what Google reads)</span>'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">No payload yet — save the product to generate.</em>');
                                                            }
                                                            $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                                            return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">'.e($json).'</pre>');
                                                        })
                                                        ->columnSpanFull(),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Active (inject into page &lt;head&gt;)</span>'))
                                                        ->inline(false),
                                                    Placeholder::make('schema_updated_at')
                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Last generated</span>'))
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
                                                    ->label('Regenerate vi')
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Regenerate JSON-LD (vi)')
                                                    ->modalDescription('Re-generate all Auto schemas for the Vietnamese locale. Manual schemas will not be affected.')
                                                    ->action(function ($livewire): void {
                                                        $product = $livewire->record;
                                                        if (! $product?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($product, 'vi');
                                                        Notification::make()->title('JSON-LD (vi) regenerated')->success()->send();
                                                        redirect(ProductResource::getUrl('edit', ['record' => $product]));
                                                    }),
                                            ]),
                                        ]),

                                    Tab::make('🇬🇧 English')
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasEn')
                                                ->relationship()
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Schemas (en)</span>'))
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
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Payload (what Google reads)</span>'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">No payload yet — save the product to generate.</em>');
                                                            }
                                                            $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                                            return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">'.e($json).'</pre>');
                                                        })
                                                        ->columnSpanFull(),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Active (inject into page &lt;head&gt;)</span>'))
                                                        ->inline(false),
                                                    Placeholder::make('schema_updated_at')
                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Last generated</span>'))
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
                                                    ->label('Regenerate en')
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Regenerate JSON-LD (en)')
                                                    ->modalDescription('Re-generate all Auto schemas for the English locale. Manual schemas will not be affected.')
                                                    ->action(function ($livewire): void {
                                                        $product = $livewire->record;
                                                        if (! $product?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($product, 'en');
                                                        Notification::make()->title('JSON-LD (en) regenerated')->success()->send();
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
                    ->label('Thumbnail')
                    ->disk('public'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('price')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('VND')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
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
                    ->label('Active'),

                Tables\Filters\SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('categories', 'name')
                    ->multiple(),

                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Product $record) => $record->is_active ? 'Hide' : 'Show')
                    ->icon(fn (Product $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Product $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (Product $record) => $record->update(['is_active' => ! $record->is_active])),

                Action::make('audit')
                    ->label('Audit')
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
