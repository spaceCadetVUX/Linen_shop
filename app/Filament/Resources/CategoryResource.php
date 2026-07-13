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
use Filament\Notifications\Notification;
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
                    Tab::make(__('admin.category.tabs.general'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\Select::make('parent_id')
                                ->label(__('admin.category.fields.parent_category'))
                                ->helperText(__('admin.category.fields.parent_category_help'))
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
                                ->label(__('admin.category.fields.internal_name'))
                                ->hint(__('admin.category.fields.internal_name_hint'))
                                ->hintIcon('heroicon-o-information-circle')
                                ->hintColor('warning')
                                ->helperText(__('admin.category.fields.internal_name_help'))
                                ->required()
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))
                                ),

                            Forms\Components\TextInput::make('slug')
                                ->label(__('admin.category.fields.internal_slug'))
                                ->hint(__('admin.category.fields.internal_slug_hint'))
                                ->hintIcon('heroicon-o-information-circle')
                                ->hintColor('warning')
                                ->helperText(__('admin.category.fields.internal_slug_help'))
                                ->required()
                                ->unique(table: Category::class, column: 'slug', ignoreRecord: true)
                                ->hidden(),

                            Forms\Components\Textarea::make('description')
                                ->label(__('admin.category.fields.internal_description'))
                                ->hint(__('admin.category.fields.internal_description_hint'))
                                ->hintIcon('heroicon-o-information-circle')
                                ->hintColor('warning')
                                ->nullable()
                                ->rows(3)
                                ->columnSpanFull()
                                ->hidden(),

                            MediaFileUpload::make('image_path')
                                ->label(__('admin.category.fields.category_image'))
                                ->image()
                                ->nullable()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),

                            Forms\Components\Toggle::make('is_active')
                                ->default(true),

                            Forms\Components\Toggle::make('show_on_landing')
                                ->label(__('admin.category.fields.show_on_landing'))
                                ->helperText(__('admin.category.fields.show_on_landing_help'))
                                ->default(false),
                        ])
                        ->columns(2),

                    // ── Content ───────────────────────────────────────────────────
                    Tab::make(__('admin.category.tabs.content'))
                        ->icon('heroicon-o-language')
                        ->schema([
                            Tabs::make('ContentLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.category.tabs.locale_vi_full'))
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.vi.name')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.display_name_vi').'</span>'))
                                                ->hint(__('admin.category.fields.display_name_vi_hint'))
                                                ->hintIcon('heroicon-o-eye')
                                                ->hintColor('success')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('translations.vi.slug', Str::slug($state ?? '')))
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.vi.slug')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.url_slug_vi').'</span>'))
                                                ->hint(__('admin.category.fields.url_slug_vi_hint'))
                                                ->hintIcon('heroicon-o-link')
                                                ->hintColor('success')
                                                ->helperText(__('admin.category.fields.slug_auto_help'))
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
                                                ->validationMessages(['unique' => __('admin.category.validation.slug_vi_unique')])
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.vi.description')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.description_vi').'</span>'))
                                                ->hint(__('admin.category.fields.description_vi_hint'))
                                                ->hintIcon('heroicon-o-eye')
                                                ->hintColor('success')
                                                ->rows(3)
                                                ->columnSpanFull(),

                                            RichEditor::make('translations.vi.rich_content')
                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.rich_content_vi').'</span>'))
                                                ->hint(__('admin.category.fields.rich_content_vi_hint'))
                                                ->hintIcon('heroicon-o-document-text')
                                                ->hintColor('success')
                                                ->plugins([MediaRichEditorPlugin::make()])
                                                ->columnSpanFull(),
                                        ]),

                                    Tab::make(__('admin.category.tabs.locale_en_full'))
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.en.name')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.display_name_en').'</span>'))
                                                ->hint(__('admin.category.fields.display_name_en_hint'))
                                                ->hintIcon('heroicon-o-eye')
                                                ->hintColor('success')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('translations.en.slug', Str::slug($state ?? '')))
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.en.slug')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.url_slug_en').'</span>'))
                                                ->hint(__('admin.category.fields.url_slug_en_hint'))
                                                ->hintIcon('heroicon-o-link')
                                                ->hintColor('success')
                                                ->helperText(__('admin.category.fields.slug_auto_help'))
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
                                                ->validationMessages(['unique' => __('admin.category.validation.slug_en_unique')])
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.en.description')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.description_en').'</span>'))
                                                ->hint(__('admin.category.fields.description_en_hint'))
                                                ->hintIcon('heroicon-o-eye')
                                                ->hintColor('success')
                                                ->rows(3)
                                                ->columnSpanFull(),

                                            RichEditor::make('translations.en.rich_content')
                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.rich_content_en').'</span>'))
                                                ->hint(__('admin.category.fields.rich_content_en_hint'))
                                                ->hintIcon('heroicon-o-document-text')
                                                ->hintColor('success')
                                                ->plugins([MediaRichEditorPlugin::make()])
                                                ->columnSpanFull(),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── SEO ───────────────────────────────────────────────────────
                    Tab::make(__('admin.category.tabs.seo'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Tabs::make('SeoLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.category.tabs.locale_vi'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaVi')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('vi'),

                                                    Section::make(__('admin.category.sections.meta_tags'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.meta_title_vi').'</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder(__('admin.category.fields.meta_title_placeholder'))
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText(__('admin.category.fields.meta_title_help'))
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.meta_description_vi').'</span>'))
                                                                ->rows(3)
                                                                ->live(debounce: 400)
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 120, 160))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 120, 160))
                                                                ->helperText(__('admin.category.fields.meta_description_help'))
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.meta_keywords_vi').'</span>'))
                                                                ->helperText(__('admin.category.fields.meta_keywords_help'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(__('admin.category.fields.canonical_url_vi'))
                                                                ->url()
                                                                ->placeholder(__('admin.category.fields.canonical_url_vi_auto'))
                                                                ->hint(__('admin.category.fields.canonical_url_vi_auto'))
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
                                                                ->label(__('admin.category.fields.robots'))
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

                                                    Section::make(__('admin.category.sections.og_vi'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.og_title_vi').'</span>'))
                                                                ->placeholder(__('admin.category.fields.auto_from_meta_title_vi'))
                                                                ->hint(__('admin.category.fields.auto_from_meta_title_vi'))
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.og_description_vi').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.category.fields.auto_from_meta_description_vi'))
                                                                ->hint(__('admin.category.fields.auto_from_meta_description_vi'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('og_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_image')
                                                                ->label(__('admin.category.fields.og_image'))
                                                                ->url()
                                                                ->placeholder(__('admin.category.fields.og_image_auto'))
                                                                ->hint(__('admin.category.fields.og_image_auto'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText(__('admin.category.fields.og_image_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->image_path) {
                                                                        $set('og_image', Storage::disk('public')->url($livewire->record->image_path));
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('og_type')
                                                                ->label(__('admin.category.fields.og_type'))
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Website->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make(__('admin.category.sections.twitter_vi'))
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label(__('admin.category.fields.twitter_card_type'))
                                                                ->options([
                                                                    'summary' => 'Summary',
                                                                    'summary_large_image' => 'Summary Large Image',
                                                                ])
                                                                ->default('summary_large_image')
                                                                ->native(false),

                                                            Forms\Components\TextInput::make('twitter_title')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.twitter_title_vi').'</span>'))
                                                                ->placeholder(__('admin.category.fields.auto_from_meta_title_vi'))
                                                                ->hint(__('admin.category.fields.auto_from_meta_title_vi'))
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
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.twitter_description_vi').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.category.fields.auto_from_meta_description_vi'))
                                                                ->hint(__('admin.category.fields.auto_from_meta_description_vi'))
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

                                    Tab::make(__('admin.category.tabs.locale_en'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('seoMetaEn')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('en'),

                                                    Section::make(__('admin.category.sections.meta_tags'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.meta_title_en').'</span>'))
                                                                ->live(debounce: 400)
                                                                ->placeholder(__('admin.category.fields.meta_title_placeholder'))
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 50, 70))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 50, 70))
                                                                ->helperText(__('admin.category.fields.meta_title_help'))
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.meta_description_en').'</span>'))
                                                                ->rows(3)
                                                                ->live(debounce: 400)
                                                                ->hint(fn (?string $state): string => self::charCounter($state, 120, 160))
                                                                ->hintColor(fn (?string $state): string => self::charCounterColor($state, 120, 160))
                                                                ->helperText(__('admin.category.fields.meta_description_help'))
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.meta_keywords_en').'</span>'))
                                                                ->helperText(__('admin.category.fields.meta_keywords_help'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(__('admin.category.fields.canonical_url_en'))
                                                                ->url()
                                                                ->placeholder(__('admin.category.fields.canonical_url_en_auto'))
                                                                ->hint(__('admin.category.fields.canonical_url_en_auto'))
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
                                                                ->label(__('admin.category.fields.robots'))
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

                                                    Section::make(__('admin.category.sections.og_en'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.og_title_en').'</span>'))
                                                                ->placeholder(__('admin.category.fields.auto_from_meta_title_en'))
                                                                ->hint(__('admin.category.fields.auto_from_meta_title_en'))
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.og_description_en').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.category.fields.auto_from_meta_description_en'))
                                                                ->hint(__('admin.category.fields.auto_from_meta_description_en'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->afterStateHydrated(function ($state, $set, $record): void {
                                                                    if (empty($state) && $record?->meta_description) {
                                                                        $set('og_description', $record->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_image')
                                                                ->label(__('admin.category.fields.og_image'))
                                                                ->url()
                                                                ->placeholder(__('admin.category.fields.og_image_auto'))
                                                                ->hint(__('admin.category.fields.og_image_auto'))
                                                                ->hintIcon('heroicon-o-sparkles')
                                                                ->hintColor('info')
                                                                ->helperText(__('admin.category.fields.og_image_help'))
                                                                ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                                    if (empty($state) && $livewire->record?->image_path) {
                                                                        $set('og_image', Storage::disk('public')->url($livewire->record->image_path));
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('og_type')
                                                                ->label(__('admin.category.fields.og_type'))
                                                                ->options(collect(OgType::cases())->mapWithKeys(
                                                                    fn (OgType $case) => [$case->value => $case->value]
                                                                ))
                                                                ->default(OgType::Website->value)
                                                                ->native(false),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsed(),

                                                    Section::make(__('admin.category.sections.twitter_en'))
                                                        ->schema([
                                                            Forms\Components\Select::make('twitter_card')
                                                                ->label(__('admin.category.fields.twitter_card_type'))
                                                                ->options([
                                                                    'summary' => 'Summary',
                                                                    'summary_large_image' => 'Summary Large Image',
                                                                ])
                                                                ->default('summary_large_image')
                                                                ->native(false),

                                                            Forms\Components\TextInput::make('twitter_title')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.twitter_title_en').'</span>'))
                                                                ->placeholder(__('admin.category.fields.auto_from_meta_title_en'))
                                                                ->hint(__('admin.category.fields.auto_from_meta_title_en'))
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
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.twitter_description_en').'</span>'))
                                                                ->rows(2)
                                                                ->placeholder(__('admin.category.fields.auto_from_meta_description_en'))
                                                                ->hint(__('admin.category.fields.auto_from_meta_description_en'))
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
                    Tab::make(__('admin.category.tabs.geo_ai'))
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Tabs::make('GeoLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.category.tabs.locale_vi'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileVi')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('vi'),

                                                    Section::make(__('admin.category.fields.ai_context'))
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.ai_summary_vi').'</span>'))
                                                                ->hint(__('admin.category.fields.ai_summary_hint'))
                                                                ->rows(4)
                                                                ->placeholder(__('admin.category.fields.ai_summary_vi_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.use_cases_vi').'</span>'))
                                                                ->hint(__('admin.category.fields.use_cases_hint'))
                                                                ->rows(3)
                                                                ->placeholder(__('admin.category.fields.use_cases_vi_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.target_audience_vi').'</span>'))
                                                                ->hint(__('admin.category.fields.target_audience_hint'))
                                                                ->placeholder(__('admin.category.fields.target_audience_vi_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.llm_context_hint_vi').'</span>'))
                                                                ->hint(__('admin.category.fields.llm_context_help'))
                                                                ->rows(2)
                                                                ->columnSpanFull(),
                                                        ]),

                                                    Section::make(__('admin.category.sections.key_facts_vi'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('key_facts')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('label')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.key_fact_label').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.category.fields.key_fact_label_placeholder_vi')),
                                                                    Forms\Components\TextInput::make('value')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.key_fact_value').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.category.fields.key_fact_value_placeholder_vi')),
                                                                ])
                                                                ->columns(2)
                                                                ->addActionLabel(__('admin.category.actions.add_key_fact'))
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),

                                                    Section::make(__('admin.category.sections.faq_vi'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('faq')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('question')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.faq_question').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.category.fields.faq_question_vi_placeholder'))
                                                                        ->columnSpanFull(),
                                                                    Forms\Components\Textarea::make('answer')
                                                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.category.fields.faq_answer').'</span>'))
                                                                        ->required()
                                                                        ->rows(3)
                                                                        ->placeholder(__('admin.category.fields.faq_answer_vi_placeholder'))
                                                                        ->columnSpanFull(),
                                                                ])
                                                                ->addActionLabel(__('admin.category.actions.add_faq'))
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),
                                                ]),
                                        ]),

                                    Tab::make(__('admin.category.tabs.locale_en'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileEn')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')
                                                        ->default('en'),

                                                    Section::make(__('admin.category.fields.ai_context'))
                                                        ->schema([
                                                            Forms\Components\Textarea::make('ai_summary')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.ai_summary_en').'</span>'))
                                                                ->hint(__('admin.category.fields.ai_summary_hint'))
                                                                ->rows(4)
                                                                ->placeholder(__('admin.category.fields.ai_summary_en_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('use_cases')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.use_cases_en').'</span>'))
                                                                ->hint(__('admin.category.fields.use_cases_hint'))
                                                                ->rows(3)
                                                                ->placeholder(__('admin.category.fields.use_cases_en_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('target_audience')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.target_audience_en').'</span>'))
                                                                ->hint(__('admin.category.fields.target_audience_hint'))
                                                                ->placeholder(__('admin.category.fields.target_audience_en_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('llm_context_hint')
                                                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.llm_context_hint_en').'</span>'))
                                                                ->hint(__('admin.category.fields.llm_context_help'))
                                                                ->rows(2)
                                                                ->columnSpanFull(),
                                                        ]),

                                                    Section::make(__('admin.category.sections.key_facts_en'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('key_facts')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('label')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.key_fact_label').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.category.fields.key_fact_label_placeholder_en')),
                                                                    Forms\Components\TextInput::make('value')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.key_fact_value').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.category.fields.key_fact_value_placeholder_en')),
                                                                ])
                                                                ->columns(2)
                                                                ->addActionLabel(__('admin.category.actions.add_key_fact'))
                                                                ->reorderable()
                                                                ->collapsible()
                                                                ->defaultItems(0)
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->collapsible(),

                                                    Section::make(__('admin.category.sections.faq_en'))
                                                        ->schema([
                                                            Forms\Components\Repeater::make('faq')
                                                                ->label('')
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('question')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.faq_question').'</span>'))
                                                                        ->required()
                                                                        ->placeholder(__('admin.category.fields.faq_question_en_placeholder'))
                                                                        ->columnSpanFull(),
                                                                    Forms\Components\Textarea::make('answer')
                                                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.category.fields.faq_answer').'</span>'))
                                                                        ->required()
                                                                        ->rows(3)
                                                                        ->placeholder(__('admin.category.fields.faq_answer_en_placeholder'))
                                                                        ->columnSpanFull(),
                                                                ])
                                                                ->addActionLabel(__('admin.category.actions.add_faq'))
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
                    Tab::make(__('admin.category.tabs.jsonld'))
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Section::make(__('admin.category.sections.jsonld_how_it_works'))
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
                                    Tab::make(__('admin.category.tabs.locale_vi'))
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasVi')
                                                ->relationship()
                                                ->label(__('admin.category.fields.jsonld_schemas_vi'))
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
                                                        ->label(__('admin.category.fields.jsonld_payload_preview'))
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
                                                        ->label(__('admin.category.fields.jsonld_active'))
                                                        ->inline(false),

                                                    Placeholder::make('schema_updated_at')
                                                        ->label(__('admin.category.fields.last_updated'))
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
                                                    ->label(__('admin.category.actions.regenerate_jsonld_vi'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading(__('admin.category.actions.regenerate_jsonld_vi_modal_heading'))
                                                    ->modalDescription(__('admin.category.actions.regenerate_jsonld_vi_modal_description'))
                                                    ->action(function ($livewire): void {
                                                        $category = $livewire->record;
                                                        if (! $category?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($category, 'vi');
                                                        Notification::make()->title(__('admin.category.notifications.jsonld_regenerated_vi'))->success()->send();
                                                        redirect(CategoryResource::getUrl('edit', ['record' => $category]));
                                                    }),
                                            ]),
                                        ]),

                                    Tab::make(__('admin.category.tabs.locale_en'))
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasEn')
                                                ->relationship()
                                                ->label(__('admin.category.fields.jsonld_schemas_en'))
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
                                                        ->label(__('admin.category.fields.jsonld_payload_preview'))
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
                                                        ->label(__('admin.category.fields.jsonld_active'))
                                                        ->inline(false),

                                                    Placeholder::make('schema_updated_at')
                                                        ->label(__('admin.category.fields.last_updated'))
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
                                                    ->label(__('admin.category.actions.regenerate_jsonld_en'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading(__('admin.category.actions.regenerate_jsonld_en_modal_heading'))
                                                    ->modalDescription(__('admin.category.actions.regenerate_jsonld_en_modal_description'))
                                                    ->action(function ($livewire): void {
                                                        $category = $livewire->record;
                                                        if (! $category?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($category, 'en');
                                                        Notification::make()->title(__('admin.category.notifications.jsonld_regenerated_en'))->success()->send();
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
                    ->label(__('admin.category.fields.parent'))
                    ->placeholder(__('admin.category.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('admin.category.fields.products_count'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.category.fields.active')),

                Tables\Filters\SelectFilter::make('parent_id')
                    ->label(__('admin.category.fields.parent'))
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
