<?php

namespace App\Filament\Resources;

use App\Enums\OgType;
use App\Filament\Resources\CategoryResource\Pages;
use App\Forms\Components\MediaFileUpload;
use App\Forms\Plugins\MediaRichEditorPlugin;
use App\Models\Category;
use App\Services\Seo\JsonldService;
use App\Support\LocaleUrl;
use BackedEnum;
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
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 20;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('CategoryTabs')
                ->tabs([

                    // ── General ───────────────────────────────────────────────────
                    Tab::make('General')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\Select::make('parent_id')
                                ->label('Parent Category')
                                ->helperText('Tối đa 2 cấp — chỉ danh mục gốc (chưa có parent) mới được chọn làm parent.')
                                ->options(function (?Category $record): array {
                                    // Only root categories (no parent of their own) can be picked as
                                    // parent — enforces a hard 2-level max. Also exclude self + all
                                    // descendants to avoid a circular reference (both hard-blocked in
                                    // CategoryObserver::saving() as a backstop).
                                    $excluded = $record ? [$record->getKey(), ...$record->descendantIds()] : [];

                                    return Category::query()
                                        ->whereNull('parent_id')
                                        ->when($excluded, fn ($q) => $q->whereNotIn('id', $excluded))
                                        ->orderBy('sort_order')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all();
                                })
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            Forms\Components\TextInput::make('name')
                                ->label('Internal Name')
                                ->hint('Dùng trong admin — không hiển thị cho người dùng')
                                ->hintIcon('heroicon-o-information-circle')
                                ->hintColor('warning')
                                ->helperText('Tên ngắn gọn để nhận biết danh mục trong hệ thống.')
                                ->required()
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))
                                ),

                            Forms\Components\TextInput::make('slug')
                                ->label('Internal Slug')
                                ->hint('Dùng trong JSON-LD và API nội bộ — không phải URL công khai')
                                ->hintIcon('heroicon-o-information-circle')
                                ->hintColor('warning')
                                ->helperText('URL công khai dùng slug từ tab Content.')
                                ->required()
                                ->unique(table: Category::class, column: 'slug', ignoreRecord: true)
                                ->hidden(),

                            Forms\Components\Textarea::make('description')
                                ->label('Internal Description')
                                ->hint('Không hiển thị trực tiếp — dùng làm gợi ý nội dung')
                                ->hintIcon('heroicon-o-information-circle')
                                ->hintColor('warning')
                                ->nullable()
                                ->rows(3)
                                ->columnSpanFull()
                                ->hidden(),

                            MediaFileUpload::make('image_path')
                                ->label('Category Image')
                                ->image()
                                ->nullable()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),

                            Forms\Components\Toggle::make('is_active')
                                ->default(true),

                            Forms\Components\Toggle::make('show_on_landing')
                                ->label('Hiện trên trang chủ (Sản phẩm nổi bật)')
                                ->helperText('Bật để danh mục này có 1 hàng sản phẩm riêng trên landing page. Thứ tự các hàng theo Sort Order.')
                                ->default(false),
                        ])
                        ->columns(2),

                    // ── Content ───────────────────────────────────────────────────
                    Tab::make('Content')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Tabs::make('ContentLocaleTabs')
                                ->tabs([
                                    Tab::make('🇻🇳 Tiếng Việt (vi)')
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.vi.name')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Tên hiển thị (vi)</span>'))
                                                ->hint('Hiển thị trên trang web cho người dùng Việt Nam')
                                                ->hintIcon('heroicon-o-eye')
                                                ->hintColor('success')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('translations.vi.slug', Str::slug($state ?? '')))
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.vi.slug')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">URL Slug (vi)</span>'))
                                                ->hint('Tạo URL: /vi/categories/{slug}')
                                                ->hintIcon('heroicon-o-link')
                                                ->hintColor('success')
                                                ->helperText('Tự động tạo từ tên. Phải unique theo từng ngôn ngữ.')
                                                ->unique(
                                                    table: 'category_translations',
                                                    column: 'slug',
                                                    ignoreRecord: false,
                                                    modifyRuleUsing: function (Unique $rule, ?Category $record): Unique {
                                                        $rule->where('locale', 'vi');
                                                        if ($record) {
                                                            $rule->ignore($record->getKey(), 'category_id');
                                                        }

                                                        return $rule;
                                                    },
                                                )
                                                ->validationMessages(['unique' => 'Slug (vi) này đã được dùng bởi danh mục khác.'])
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.vi.description')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Mô tả (vi)</span>'))
                                                ->hint('Hiển thị trên trang danh mục — Google đọc để hiểu nội dung')
                                                ->hintIcon('heroicon-o-eye')
                                                ->hintColor('success')
                                                ->rows(3)
                                                ->columnSpanFull(),

                                            RichEditor::make('translations.vi.rich_content')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Nội dung phong phú (vi)</span>'))
                                                ->hint('Nội dung dài, có thể chèn ảnh — hiển thị ở phần dưới trang danh mục')
                                                ->hintIcon('heroicon-o-document-text')
                                                ->hintColor('success')
                                                ->plugins([MediaRichEditorPlugin::make()])
                                                ->columnSpanFull(),
                                        ]),

                                    Tab::make('🇬🇧 English (en)')
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.en.name')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Display Name (en)</span>'))
                                                ->hint('Shown on the website to English-speaking visitors')
                                                ->hintIcon('heroicon-o-eye')
                                                ->hintColor('success')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('translations.en.slug', Str::slug($state ?? '')))
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.en.slug')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">URL Slug (en)</span>'))
                                                ->hint('Creates URL: /en/categories/{slug}')
                                                ->hintIcon('heroicon-o-link')
                                                ->hintColor('success')
                                                ->helperText('Auto-generated from name. Must be unique per locale.')
                                                ->unique(
                                                    table: 'category_translations',
                                                    column: 'slug',
                                                    ignoreRecord: false,
                                                    modifyRuleUsing: function (Unique $rule, ?Category $record): Unique {
                                                        $rule->where('locale', 'en');
                                                        if ($record) {
                                                            $rule->ignore($record->getKey(), 'category_id');
                                                        }

                                                        return $rule;
                                                    },
                                                )
                                                ->validationMessages(['unique' => 'This slug (en) is already used by another category.'])
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.en.description')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Description (en)</span>'))
                                                ->hint('Shown on the category page — Google reads this to understand content')
                                                ->hintIcon('heroicon-o-eye')
                                                ->hintColor('success')
                                                ->rows(3)
                                                ->columnSpanFull(),

                                            RichEditor::make('translations.en.rich_content')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Rich Content (en)</span>'))
                                                ->hint('Long-form content with images — displayed at the bottom of the category page')
                                                ->hintIcon('heroicon-o-document-text')
                                                ->hintColor('success')
                                                ->plugins([MediaRichEditorPlugin::make()])
                                                ->columnSpanFull(),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── SEO ───────────────────────────────────────────────────────
                    Tab::make('SEO')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Tabs::make('SeoLocaleTabs')
                                ->tabs([
                                    Tab::make('🇻🇳 Tiếng Việt')
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaVi')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('vi'),

                                                    Section::make('Meta Tags')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Meta Title (vi)</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder('Tự điền từ tên danh mục')
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText('Tối ưu: 50–70 ký tự.')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $name = $livewire->record?->translation('vi')?->name ?? $livewire->record?->name;
                                                                        if ($name) {
                                                                            $set('meta_title', $name);
                                                                        }
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
                                                                        $desc = $livewire->record?->translation('vi')?->description;
                                                                        if ($desc) {
                                                                            $set('meta_description', $desc);
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('meta_keywords')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Meta Keywords (vi)</span>'))
                                                                ->helperText('Phân cách bằng dấu phẩy')
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label('Canonical URL (vi)')
                                                                ->url()
                                                                ->placeholder('Tự tạo từ slug (vi)')
                                                                ->hint('Tự tạo từ slug (vi)')
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $slug = $livewire->record?->translation('vi')?->slug ?? $livewire->record?->slug;
                                                                        if ($slug) {
                                                                            $set('canonical_url', LocaleUrl::for('category', $slug, 'vi'));
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('robots')
                                                                ->label('Robots')
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
                                                                ->label('OG Image URL')
                                                                ->url()
                                                                ->placeholder('Tự điền từ ảnh danh mục')
                                                                ->hint('Tự điền từ ảnh danh mục')
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText('Khuyến nghị: 1200×630px.')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->image_path) {
                                                                        $set('og_image', Storage::disk('public')->url($livewire->record->image_path));
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('og_type')
                                                                ->label('OG Type')
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Website->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make('Twitter Card (vi)')
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label('Card Type')
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
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('en'),

                                                    Section::make('Meta Tags')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Meta Title (en)</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder('Auto-filled from category name')
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
                                                                        $desc = $livewire->record?->translation('en')?->description;
                                                                        if ($desc) {
                                                                            $set('meta_description', $desc);
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('meta_keywords')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Meta Keywords (en)</span>'))
                                                                ->helperText('Comma-separated')
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label('Canonical URL (en)')
                                                                ->url()
                                                                ->placeholder('Auto-generated from slug (en)')
                                                                ->hint('Auto-generated from slug (en)')
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state)) {
                                                                        $slug = $livewire->record?->translation('en')?->slug ?? $livewire->record?->slug;
                                                                        if ($slug) {
                                                                            $set('canonical_url', LocaleUrl::for('category', $slug, 'en'));
                                                                        }
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('robots')
                                                                ->label('Robots')
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
                                                                ->label('OG Image URL')
                                                                ->url()
                                                                ->placeholder('Auto-filled from category image')
                                                                ->hint('Auto-filled from category image')
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText('Recommended: 1200×630px.')
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->image_path) {
                                                                        $set('og_image', Storage::disk('public')->url($livewire->record->image_path));
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('og_type')
                                                                ->label('OG Type')
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Website->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make('Twitter Card (en)')
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label('Card Type')
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
                                ]),
                        ]),

                    // ── GEO / AI ──────────────────────────────────────────────────
                    Tab::make('GEO / AI')
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Tabs::make('GeoLocaleTabs')
                                ->tabs([
                                    Tab::make('🇻🇳 Tiếng Việt')
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileVi')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('vi'),

                                                    Section::make('AI Context')
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">AI Summary (vi)</span>'))
                                                                ->hint('Đoạn tóm tắt ngắn cho AI / chatbot hiểu danh mục này')
                                                                ->rows(4)
                                                                ->placeholder('Mô tả 2–4 câu về danh mục: sản phẩm nào có trong đó, đối tượng khách hàng, điểm nổi bật...')
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Use Cases (vi)</span>'))
                                                                ->hint('Ứng dụng thực tế — AI dùng để trả lời "danh mục này phù hợp cho ai / dùng ở đâu"')
                                                                ->rows(3)
                                                                ->placeholder('VD: Phù hợp cho nhà ở, văn phòng, công trình thương mại...')
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Target Audience (vi)</span>'))
                                                                ->hint('Đối tượng mục tiêu — AI dùng để phân loại và gợi ý')
                                                                ->placeholder('VD: Kỹ sư điện, nhà thầu, hộ gia đình...')
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">LLM Context Hint (vi)</span>'))
                                                                ->hint('Gợi ý thêm cho LLM khi sinh nội dung về danh mục')
                                                                ->rows(2)
                                                                ->columnSpanFull(),
                                                        ]),

                                                    Section::make('Key Facts (vi)')
                                                        ->schema([
                                                            Forms\Components\Repeater::make('key_facts')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('label')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Nhãn</span>'))
                                                                        ->required()
                                                                        ->placeholder('VD: Số sản phẩm'),
                                                                    Forms\Components\TextInput::make('value')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Giá trị</span>'))
                                                                        ->required()
                                                                        ->placeholder('VD: 120+'),
                                                                ])
                                                                ->columns(2)
                                                                ->addActionLabel('Thêm fact')
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),

                                                    Section::make('FAQ (vi)')
                                                        ->schema([
                                                            Forms\Components\Repeater::make('faq')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('question')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Câu hỏi</span>'))
                                                                        ->required()
                                                                        ->placeholder('VD: Danh mục này có những sản phẩm gì?')
                                                                        ->columnSpanFull(),
                                                                    Forms\Components\Textarea::make('answer')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Trả lời</span>'))
                                                                        ->required()
                                                                        ->rows(3)
                                                                        ->placeholder('Câu trả lời ngắn gọn, rõ ràng...')
                                                                        ->columnSpanFull(),
                                                                ])
                                                                ->addActionLabel('Thêm câu hỏi')
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),
                                                ]),
                                        ]),

                                    Tab::make('🇬🇧 English')
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileEn')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('en'),

                                                    Section::make('AI Context')
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">AI Summary (en)</span>'))
                                                                ->hint('Short summary for AI / chatbot understanding of this category')
                                                                ->rows(4)
                                                                ->placeholder('Describe the category in 2–4 sentences: what products, who it\'s for, key highlights...')
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Use Cases (en)</span>'))
                                                                ->hint('Practical applications — AI uses this to answer "who is this for / where is it used"')
                                                                ->rows(3)
                                                                ->placeholder('E.g. Suitable for residential, commercial, and industrial projects...')
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Target Audience (en)</span>'))
                                                                ->hint('Target demographic — AI uses this for classification and recommendations')
                                                                ->placeholder('E.g. Electricians, contractors, homeowners...')
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">LLM Context Hint (en)</span>'))
                                                                ->hint('Additional context hint for LLMs when generating content about this category')
                                                                ->rows(2)
                                                                ->columnSpanFull(),
                                                        ]),

                                                    Section::make('Key Facts (en)')
                                                        ->schema([
                                                            Forms\Components\Repeater::make('key_facts')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('label')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Label</span>'))
                                                                        ->required()
                                                                        ->placeholder('E.g. Products count'),
                                                                    Forms\Components\TextInput::make('value')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Value</span>'))
                                                                        ->required()
                                                                        ->placeholder('E.g. 120+'),
                                                                ])
                                                                ->columns(2)
                                                                ->addActionLabel('Add fact')
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),

                                                    Section::make('FAQ (en)')
                                                        ->schema([
                                                            Forms\Components\Repeater::make('faq')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('question')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Question</span>'))
                                                                        ->required()
                                                                        ->placeholder('E.g. What products does this category include?')
                                                                        ->columnSpanFull(),
                                                                    Forms\Components\Textarea::make('answer')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Answer</span>'))
                                                                        ->required()
                                                                        ->rows(3)
                                                                        ->placeholder('Short, clear answer...')
                                                                        ->columnSpanFull(),
                                                                ])
                                                                ->addActionLabel('Add FAQ')
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
                    Tab::make('JSON-LD')
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Section::make('Schemas hoạt động như thế nào?')
                                ->schema([
                                    Placeholder::make('jsonld_info')
                                        ->label('')
                                        ->content(new HtmlString('
                                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                <li>Schemas được tạo tự động bởi <strong>CategoryObserver</strong> mỗi khi lưu danh mục.</li>
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
                                    Tab::make('🇻🇳 Tiếng Việt')
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasVi')
                                                ->relationship()
                                                ->label('Schemas (vi)')
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
                                                        ->label('Payload (Google reads this)')
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">Chưa có payload — lưu danh mục để tạo.</em>');
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
                                                        ->label('Active (inject vào <head> trang)')
                                                        ->inline(false),

                                                    Placeholder::make('schema_updated_at')
                                                        ->label('Cập nhật lần cuối')
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
                                                        $category = $livewire->record;
                                                        if (! $category?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($category, 'vi');
                                                        Notification::make()->title('JSON-LD (vi) đã được regenerate')->success()->send();
                                                        redirect(CategoryResource::getUrl('edit', ['record' => $category]));
                                                    }),
                                            ]),
                                        ]),

                                    Tab::make('🇬🇧 English')
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasEn')
                                                ->relationship()
                                                ->label('Schemas (en)')
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
                                                        ->label('Payload (Google reads this)')
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">No payload yet — save the category to generate.</em>');
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
                                                        ->label('Active (inject into <head>)')
                                                        ->inline(false),

                                                    Placeholder::make('schema_updated_at')
                                                        ->label('Last updated')
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
                                                        $category = $livewire->record;
                                                        if (! $category?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($category, 'en');
                                                        Notification::make()->title('JSON-LD (en) regenerated')->success()->send();
                                                        redirect(CategoryResource::getUrl('edit', ['record' => $category]));
                                                    }),
                                            ]),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('products'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->relationship('parent', 'name'),

                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
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
