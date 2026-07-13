<?php

namespace App\Filament\Resources;

use App\Enums\OgType;
use App\Filament\Resources\BlogCategoryResource\Pages;
use App\Models\BlogCategory;
use App\Forms\Components\MediaFileUpload;
use App\Forms\Plugins\MediaRichEditorPlugin;
use App\Support\LocaleUrl;
use Filament\Forms\Components\RichEditor;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-folder';

    protected static \UnitEnum|string|null $navigationGroup = 'Blog';

    protected static ?int $navigationSort = 20;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('admin.blog_category.fields.internal_name'))
                ->hint(__('admin.blog_category.fields.internal_name_hint'))
                ->hintIcon('heroicon-o-information-circle')
                ->hintColor('warning')
                ->helperText(__('admin.blog_category.fields.internal_name_help'))
                ->required()
                ->live(debounce: 500)
                ->afterStateUpdated(fn (Set $set, ?string $state) =>
                    $set('slug', Str::slug($state ?? ''))
                ),

            Forms\Components\TextInput::make('slug')
                ->label(__('admin.blog_category.fields.internal_slug'))
                ->hint(__('admin.blog_category.fields.internal_slug_hint'))
                ->hintIcon('heroicon-o-information-circle')
                ->hintColor('warning')
                ->helperText(__('admin.blog_category.fields.internal_slug_help'))
                ->required()
                ->unique(table: BlogCategory::class, column: 'slug', ignoreRecord: true),

            Forms\Components\Textarea::make('description')
                ->label(__('admin.blog_category.fields.internal_description'))
                ->hint(__('admin.blog_category.fields.internal_description_hint'))
                ->hintIcon('heroicon-o-information-circle')
                ->hintColor('warning')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('is_active')
                ->default(true),

            // ── Translations ──────────────────────────────────────────────────
            Section::make(__('admin.blog_category.sections.translations'))
                ->icon('heroicon-o-language')
                ->schema([
                    Tabs::make('LocaleTabs')
                        ->tabs([
                            Tab::make(__('admin.blog_category.tabs.locale_vi_full'))
                                ->schema([
                                    Forms\Components\TextInput::make('translations.vi.name')
                                        ->label(__('admin.blog_category.fields.display_name_vi'))
                                        ->hint(__('admin.blog_category.fields.display_name_vi_hint'))
                                        ->hintIcon('heroicon-o-eye')
                                        ->hintColor('success')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, Set $set) =>
                                            $set('translations.vi.slug', Str::slug($state ?? '')))
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('translations.vi.slug')
                                        ->label(__('admin.blog_category.fields.url_slug_vi'))
                                        ->hint(__('admin.blog_category.fields.url_slug_vi_hint'))
                                        ->hintIcon('heroicon-o-link')
                                        ->hintColor('success')
                                        ->helperText(__('admin.blog_category.fields.slug_auto_help'))
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('translations.vi.description')
                                        ->label(__('admin.blog_category.fields.description_vi'))
                                        ->hint(__('admin.blog_category.fields.description_vi_hint'))
                                        ->hintIcon('heroicon-o-eye')
                                        ->hintColor('success')
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    RichEditor::make('translations.vi.rich_content')
                                        ->label(__('admin.blog_category.fields.rich_content_vi'))
                                        ->hint(__('admin.blog_category.fields.rich_content_vi_hint'))
                                        ->hintIcon('heroicon-o-document-text')
                                        ->hintColor('success')
                                        ->plugins([MediaRichEditorPlugin::make()])
                                        ->columnSpanFull(),
                                ]),

                            Tab::make(__('admin.blog_category.tabs.locale_en_full'))
                                ->schema([
                                    Forms\Components\TextInput::make('translations.en.name')
                                        ->label(__('admin.blog_category.fields.display_name_en'))
                                        ->hint(__('admin.blog_category.fields.display_name_en_hint'))
                                        ->hintIcon('heroicon-o-eye')
                                        ->hintColor('success')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, Set $set) =>
                                            $set('translations.en.slug', Str::slug($state ?? '')))
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('translations.en.slug')
                                        ->label(__('admin.blog_category.fields.url_slug_en'))
                                        ->hint(__('admin.blog_category.fields.url_slug_en_hint'))
                                        ->hintIcon('heroicon-o-link')
                                        ->hintColor('success')
                                        ->helperText(__('admin.blog_category.fields.slug_auto_help'))
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('translations.en.description')
                                        ->label(__('admin.blog_category.fields.description_en'))
                                        ->hint(__('admin.blog_category.fields.description_en_hint'))
                                        ->hintIcon('heroicon-o-eye')
                                        ->hintColor('success')
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    RichEditor::make('translations.en.rich_content')
                                        ->label(__('admin.blog_category.fields.rich_content_en'))
                                        ->hint(__('admin.blog_category.fields.rich_content_en_hint'))
                                        ->hintIcon('heroicon-o-document-text')
                                        ->hintColor('success')
                                        ->plugins([MediaRichEditorPlugin::make()])
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->columnSpanFull(),

            // ── GEO / AI ─────────────────────────────────────────────────────
            Section::make(__('admin.blog_category.sections.geo_ai'))
                ->icon('heroicon-o-cpu-chip')
                ->schema([
                    Tabs::make('GeoLocaleTabs')
                        ->tabs([
                            Tab::make(__('admin.blog_category.tabs.locale_vi'))
                                ->schema([
                                    Group::make()
                                        ->relationship('geoProfileVi')
                                        ->schema([
                                            Forms\Components\Hidden::make('locale')->default('vi'),

                                            Section::make(__('admin.blog_category.fields.ai_context'))
                                                ->schema([
                                                    Forms\Components\Textarea::make('ai_summary')
                                                        ->label(__('admin.blog_category.fields.ai_summary_vi'))
                                                        ->hint(__('admin.blog_category.fields.ai_summary_hint'))
                                                        ->rows(4)
                                                        ->placeholder(__('admin.blog_category.fields.ai_summary_vi_placeholder'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('use_cases')
                                                        ->label(__('admin.blog_category.fields.use_cases_vi'))
                                                        ->hint(__('admin.blog_category.fields.use_cases_hint'))
                                                        ->rows(3)
                                                        ->placeholder(__('admin.blog_category.fields.use_cases_vi_placeholder'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\TextInput::make('target_audience')
                                                        ->label(__('admin.blog_category.fields.target_audience_vi'))
                                                        ->hint(__('admin.blog_category.fields.target_audience_hint'))
                                                        ->placeholder(__('admin.blog_category.fields.target_audience_vi_placeholder'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('llm_context_hint')
                                                        ->label(__('admin.blog_category.fields.llm_context_hint_vi'))
                                                        ->hint(__('admin.blog_category.fields.llm_context_help'))
                                                        ->rows(2)
                                                        ->columnSpanFull(),
                                                ]),

                                            Section::make(__('admin.blog_category.sections.key_facts_vi'))
                                                ->schema([
                                                    Forms\Components\Repeater::make('key_facts')
                                                        ->label('')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('label')
                                                                ->label(__('admin.blog_category.fields.key_fact_label'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_category.fields.key_fact_label_placeholder_vi')),
                                                            Forms\Components\TextInput::make('value')
                                                                ->label(__('admin.blog_category.fields.key_fact_value'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_category.fields.key_fact_value_placeholder_vi')),
                                                        ])
                                                        ->columns(2)
                                                        ->addActionLabel(__('admin.blog_category.actions.add_key_fact'))
                                                        ->reorderable()
                                                        ->collapsible()
                                                        ->defaultItems(0)
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsible(),

                                            Section::make(__('admin.blog_category.sections.faq_vi'))
                                                ->schema([
                                                    Forms\Components\Repeater::make('faq')
                                                        ->label('')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('question')
                                                                ->label(__('admin.blog_category.fields.faq_question'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_category.fields.faq_question_vi_placeholder'))
                                                                ->columnSpanFull(),
                                                            Forms\Components\Textarea::make('answer')
                                                                ->label(__('admin.blog_category.fields.faq_answer'))
                                                                ->required()
                                                                ->rows(3)
                                                                ->placeholder(__('admin.blog_category.fields.faq_answer_vi_placeholder'))
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->addActionLabel(__('admin.blog_category.actions.add_faq'))
                                                        ->reorderable()
                                                        ->collapsible()
                                                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                                        ->defaultItems(0)
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsible(),
                                        ]),
                                ]),

                            Tab::make(__('admin.blog_category.tabs.locale_en'))
                                ->schema([
                                    Group::make()
                                        ->relationship('geoProfileEn')
                                        ->schema([
                                            Forms\Components\Hidden::make('locale')->default('en'),

                                            Section::make(__('admin.blog_category.fields.ai_context'))
                                                ->schema([
                                                    Forms\Components\Textarea::make('ai_summary')
                                                        ->label(__('admin.blog_category.fields.ai_summary_en'))
                                                        ->hint(__('admin.blog_category.fields.ai_summary_hint'))
                                                        ->rows(4)
                                                        ->placeholder(__('admin.blog_category.fields.ai_summary_en_placeholder'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('use_cases')
                                                        ->label(__('admin.blog_category.fields.use_cases_en'))
                                                        ->hint(__('admin.blog_category.fields.use_cases_hint'))
                                                        ->rows(3)
                                                        ->placeholder(__('admin.blog_category.fields.use_cases_en_placeholder'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\TextInput::make('target_audience')
                                                        ->label(__('admin.blog_category.fields.target_audience_en'))
                                                        ->hint(__('admin.blog_category.fields.target_audience_hint'))
                                                        ->placeholder(__('admin.blog_category.fields.target_audience_en_placeholder'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('llm_context_hint')
                                                        ->label(__('admin.blog_category.fields.llm_context_hint_en'))
                                                        ->hint(__('admin.blog_category.fields.llm_context_help'))
                                                        ->rows(2)
                                                        ->columnSpanFull(),
                                                ]),

                                            Section::make(__('admin.blog_category.sections.key_facts_en'))
                                                ->schema([
                                                    Forms\Components\Repeater::make('key_facts')
                                                        ->label('')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('label')
                                                                ->label(__('admin.blog_category.fields.key_fact_label'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_category.fields.key_fact_label_placeholder_en')),
                                                            Forms\Components\TextInput::make('value')
                                                                ->label(__('admin.blog_category.fields.key_fact_value'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_category.fields.key_fact_value_placeholder_en')),
                                                        ])
                                                        ->columns(2)
                                                        ->addActionLabel(__('admin.blog_category.actions.add_key_fact'))
                                                        ->reorderable()
                                                        ->collapsible()
                                                        ->defaultItems(0)
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsible(),

                                            Section::make(__('admin.blog_category.sections.faq_en'))
                                                ->schema([
                                                    Forms\Components\Repeater::make('faq')
                                                        ->label('')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('question')
                                                                ->label(__('admin.blog_category.fields.faq_question'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_category.fields.faq_question_en_placeholder'))
                                                                ->columnSpanFull(),
                                                            Forms\Components\Textarea::make('answer')
                                                                ->label(__('admin.blog_category.fields.faq_answer'))
                                                                ->required()
                                                                ->rows(3)
                                                                ->placeholder(__('admin.blog_category.fields.faq_answer_en_placeholder'))
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->addActionLabel(__('admin.blog_category.actions.add_faq'))
                                                        ->reorderable()
                                                        ->collapsible()
                                                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                                        ->defaultItems(0)
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsible(),
                                        ]),
                                ]),
                        ])
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->columnSpanFull(),

            // ── SEO ───────────────────────────────────────────────────────────
            Section::make(__('admin.blog_category.sections.seo'))
                ->icon('heroicon-o-magnifying-glass')
                ->schema([
                    Tabs::make('SeoLocaleTabs')
                        ->tabs([
                            Tabs\Tab::make(__('admin.blog_category.tabs.locale_vi'))
                                ->schema([
                                    Group::make()
                                        ->relationship('seoMetaVi')
                                        ->mutateRelationshipDataBeforeCreateUsing(
                                            fn (array $data) => ['locale' => 'vi', ...$data]
                                        )
                                        ->schema([
                                            Section::make(__('admin.blog_category.sections.meta_tags'))
                                                ->schema([
                                                    Forms\Components\TextInput::make('meta_title')
                                                        ->label(__('admin.blog_category.fields.meta_title_vi'))
                                                        ->placeholder(__('admin.blog_category.fields.meta_title_placeholder'))
                                                        ->helperText(__('admin.blog_category.fields.meta_title_help'))
                                                        ->live(debounce: 500)
                                                        ->hint(fn ($state): string => mb_strlen($state ?? '') . '/60')
                                                        ->hintColor(fn ($state): string => static::charCounterColor($state, 50, 60))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('meta_description')
                                                        ->label(__('admin.blog_category.fields.meta_description_vi'))
                                                        ->placeholder(__('admin.blog_category.fields.meta_description_placeholder'))
                                                        ->helperText(__('admin.blog_category.fields.meta_description_help'))
                                                        ->rows(3)
                                                        ->live(debounce: 500)
                                                        ->hint(fn ($state): string => mb_strlen($state ?? '') . '/155')
                                                        ->hintColor(fn ($state): string => static::charCounterColor($state, 120, 155))
                                                        ->columnSpanFull(),

                                                    Forms\Components\TextInput::make('meta_keywords')
                                                        ->label(__('admin.blog_category.fields.meta_keywords_vi'))
                                                        ->helperText(__('admin.blog_category.fields.meta_keywords_help'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\TextInput::make('canonical_url')
                                                        ->label(__('admin.blog_category.fields.canonical_url_vi'))
                                                        ->url()
                                                        ->placeholder(__('admin.blog_category.fields.canonical_url_vi_auto'))
                                                        ->hint(__('admin.blog_category.fields.canonical_url_vi_auto'))
                                                        ->hintIcon('heroicon-o-sparkles')
                                                        ->hintColor('info')
                                                        ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                            if (empty($state)) {
                                                                $slug = $livewire->record?->translation('vi')?->slug ?? $livewire->record?->slug;
                                                                if ($slug) {
                                                                    $set('canonical_url', LocaleUrl::for('blog_category', $slug, 'vi'));
                                                                }
                                                            }
                                                        })
                                                        ->columnSpanFull(),

                                                    Forms\Components\Select::make('robots')
                                                        ->label(__('admin.blog_category.fields.robots_vi'))
                                                        ->options([
                                                            'index,follow'     => 'index, follow — Default',
                                                            'noindex,follow'   => 'noindex, follow — Exclude from index',
                                                            'noindex,nofollow' => 'noindex, nofollow — Block completely',
                                                        ])
                                                        ->default('index,follow')
                                                        ->native(false),
                                                ])
                                                ->columns(2),

                                            Section::make(__('admin.blog_category.sections.og_vi'))
                                                ->schema([
                                                    Forms\Components\TextInput::make('og_title')
                                                        ->label(__('admin.blog_category.fields.og_title_vi'))
                                                        ->placeholder(__('admin.blog_category.fields.auto_from_meta_title_vi'))
                                                        ->hint(__('admin.blog_category.fields.auto_from_meta_title_vi'))
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
                                                        ->label(__('admin.blog_category.fields.og_description_vi'))
                                                        ->rows(2)
                                                        ->placeholder(__('admin.blog_category.fields.auto_from_meta_description_vi'))
                                                        ->hint(__('admin.blog_category.fields.auto_from_meta_description_vi'))
                                                        ->hintIcon('heroicon-o-sparkles')
                                                        ->hintColor('info')
                                                        ->afterStateHydrated(function ($state, $set, $record): void {
                                                            if (empty($state) && $record?->meta_description) {
                                                                $set('og_description', $record->meta_description);
                                                            }
                                                        })
                                                        ->columnSpanFull(),

                                                    MediaFileUpload::make('og_image')
                                                        ->label(__('admin.blog_category.fields.og_image_vi'))
                                                        ->helperText(__('admin.blog_category.fields.og_image_help'))
                                                        ->image()
                                                        ->nullable()
                                                        ->columnSpanFull(),

                                                    Forms\Components\Select::make('og_type')
                                                        ->label(__('admin.blog_category.fields.og_type'))
                                                        ->options(collect(OgType::cases())->mapWithKeys(
                                                            fn (OgType $case) => [$case->value => $case->value]
                                                        ))
                                                        ->default(OgType::Website->value)
                                                        ->native(false),
                                                ])
                                                ->columns(2)
                                                ->collapsed(),

                                            Section::make(__('admin.blog_category.sections.twitter_vi'))
                                                ->schema([
                                                    Forms\Components\Select::make('twitter_card')
                                                        ->label(__('admin.blog_category.fields.twitter_card_type'))
                                                        ->options([
                                                            'summary'             => 'Summary',
                                                            'summary_large_image' => 'Summary Large Image',
                                                        ])
                                                        ->default('summary_large_image')
                                                        ->native(false),

                                                    Forms\Components\TextInput::make('twitter_title')
                                                        ->label(__('admin.blog_category.fields.twitter_title_vi'))
                                                        ->placeholder(__('admin.blog_category.fields.auto_from_meta_title_vi'))
                                                        ->hint(__('admin.blog_category.fields.auto_from_meta_title_vi'))
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
                                                        ->label(__('admin.blog_category.fields.twitter_description_vi'))
                                                        ->rows(2)
                                                        ->placeholder(__('admin.blog_category.fields.auto_from_meta_description_vi'))
                                                        ->hint(__('admin.blog_category.fields.auto_from_meta_description_vi'))
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

                            Tabs\Tab::make(__('admin.blog_category.tabs.locale_en'))
                                ->schema([
                                    Group::make()
                                        ->relationship('seoMetaEn')
                                        ->mutateRelationshipDataBeforeCreateUsing(
                                            fn (array $data) => ['locale' => 'en', ...$data]
                                        )
                                        ->schema([
                                            Section::make(__('admin.blog_category.sections.meta_tags'))
                                                ->schema([
                                                    Forms\Components\TextInput::make('meta_title')
                                                        ->label(__('admin.blog_category.fields.meta_title_en'))
                                                        ->placeholder(__('admin.blog_category.fields.meta_title_placeholder'))
                                                        ->helperText(__('admin.blog_category.fields.meta_title_help'))
                                                        ->live(debounce: 500)
                                                        ->hint(fn ($state): string => mb_strlen($state ?? '') . '/60')
                                                        ->hintColor(fn ($state): string => static::charCounterColor($state, 50, 60))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('meta_description')
                                                        ->label(__('admin.blog_category.fields.meta_description_en'))
                                                        ->placeholder(__('admin.blog_category.fields.meta_description_placeholder'))
                                                        ->helperText(__('admin.blog_category.fields.meta_description_help'))
                                                        ->rows(3)
                                                        ->live(debounce: 500)
                                                        ->hint(fn ($state): string => mb_strlen($state ?? '') . '/155')
                                                        ->hintColor(fn ($state): string => static::charCounterColor($state, 120, 155))
                                                        ->columnSpanFull(),

                                                    Forms\Components\TextInput::make('meta_keywords')
                                                        ->label(__('admin.blog_category.fields.meta_keywords_en'))
                                                        ->helperText(__('admin.blog_category.fields.meta_keywords_help'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\TextInput::make('canonical_url')
                                                        ->label(__('admin.blog_category.fields.canonical_url_en'))
                                                        ->url()
                                                        ->placeholder(__('admin.blog_category.fields.canonical_url_en_auto'))
                                                        ->hint(__('admin.blog_category.fields.canonical_url_en_auto'))
                                                        ->hintIcon('heroicon-o-sparkles')
                                                        ->hintColor('info')
                                                        ->afterStateHydrated(function ($state, $set, $livewire): void {
                                                            if (empty($state)) {
                                                                $slug = $livewire->record?->translation('en')?->slug ?? $livewire->record?->slug;
                                                                if ($slug) {
                                                                    $set('canonical_url', LocaleUrl::for('blog_category', $slug, 'en'));
                                                                }
                                                            }
                                                        })
                                                        ->columnSpanFull(),

                                                    Forms\Components\Select::make('robots')
                                                        ->label(__('admin.blog_category.fields.robots_en'))
                                                        ->options([
                                                            'index,follow'     => 'index, follow — Default',
                                                            'noindex,follow'   => 'noindex, follow — Exclude from index',
                                                            'noindex,nofollow' => 'noindex, nofollow — Block completely',
                                                        ])
                                                        ->default('index,follow')
                                                        ->native(false),
                                                ])
                                                ->columns(2),

                                            Section::make(__('admin.blog_category.sections.og_en'))
                                                ->schema([
                                                    Forms\Components\TextInput::make('og_title')
                                                        ->label(__('admin.blog_category.fields.og_title_en'))
                                                        ->placeholder(__('admin.blog_category.fields.auto_from_meta_title_en'))
                                                        ->hint(__('admin.blog_category.fields.auto_from_meta_title_en'))
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
                                                        ->label(__('admin.blog_category.fields.og_description_en'))
                                                        ->rows(2)
                                                        ->placeholder(__('admin.blog_category.fields.auto_from_meta_description_en'))
                                                        ->hint(__('admin.blog_category.fields.auto_from_meta_description_en'))
                                                        ->hintIcon('heroicon-o-sparkles')
                                                        ->hintColor('info')
                                                        ->afterStateHydrated(function ($state, $set, $record): void {
                                                            if (empty($state) && $record?->meta_description) {
                                                                $set('og_description', $record->meta_description);
                                                            }
                                                        })
                                                        ->columnSpanFull(),

                                                    MediaFileUpload::make('og_image')
                                                        ->label(__('admin.blog_category.fields.og_image_en'))
                                                        ->helperText(__('admin.blog_category.fields.og_image_help'))
                                                        ->image()
                                                        ->nullable()
                                                        ->columnSpanFull(),

                                                    Forms\Components\Select::make('og_type')
                                                        ->label(__('admin.blog_category.fields.og_type'))
                                                        ->options(collect(OgType::cases())->mapWithKeys(
                                                            fn (OgType $case) => [$case->value => $case->value]
                                                        ))
                                                        ->default(OgType::Website->value)
                                                        ->native(false),
                                                ])
                                                ->columns(2)
                                                ->collapsed(),

                                            Section::make(__('admin.blog_category.sections.twitter_en'))
                                                ->schema([
                                                    Forms\Components\Select::make('twitter_card')
                                                        ->label(__('admin.blog_category.fields.twitter_card_type'))
                                                        ->options([
                                                            'summary'             => 'Summary',
                                                            'summary_large_image' => 'Summary Large Image',
                                                        ])
                                                        ->default('summary_large_image')
                                                        ->native(false),

                                                    Forms\Components\TextInput::make('twitter_title')
                                                        ->label(__('admin.blog_category.fields.twitter_title_en'))
                                                        ->placeholder(__('admin.blog_category.fields.auto_from_meta_title_en'))
                                                        ->hint(__('admin.blog_category.fields.auto_from_meta_title_en'))
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
                                                        ->label(__('admin.blog_category.fields.twitter_description_en'))
                                                        ->rows(2)
                                                        ->placeholder(__('admin.blog_category.fields.auto_from_meta_description_en'))
                                                        ->hint(__('admin.blog_category.fields.auto_from_meta_description_en'))
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
                ])
                ->collapsible()
                ->columnSpanFull(),

            // ── JSON-LD ───────────────────────────────────────────────────────
            Section::make(__('admin.blog_category.sections.jsonld'))
                ->icon('heroicon-o-code-bracket')
                ->schema([
                    Placeholder::make('jsonld_info')
                        ->label('')
                        ->content(new HtmlString('
                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                <li>Schemas marked <strong>Auto</strong> are regenerated every time this category is saved.</li>
                                <li>Toggle <strong>Active</strong> to include / exclude a schema from the page <code>&lt;head&gt;</code>.</li>
                            </ul>
                        '))
                        ->columnSpanFull(),

                    Tabs::make('JsonldLocaleTabs')
                        ->tabs([
                            Tabs\Tab::make(__('admin.blog_category.tabs.locale_vi'))
                                ->schema([
                                    Forms\Components\Repeater::make('jsonldSchemasVi')
                                        ->relationship()
                                        ->label(__('admin.blog_category.fields.jsonld_schemas_vi'))
                                        ->schema([
                                            Placeholder::make('schema_header')
                                                ->label('')
                                                ->content(function ($record): HtmlString {
                                                    if (! $record) { return new HtmlString(''); }
                                                    $type  = $record->schema_type?->value ?? '—';
                                                    $label = e($record->label ?? '');
                                                    $auto  = $record->is_auto_generated
                                                        ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#fef9c3;color:#854d0e;">⚡ Auto</span>'
                                                        : '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;">✎ Manual</span>';
                                                    return new HtmlString("<div style='display:flex;align-items:center;gap:10px;flex-wrap:wrap;'><span style='font-weight:700;font-size:0.95rem;color:#1e293b;'>{$type}</span>" . (filled($label) ? "<span style='color:#64748b;font-size:0.85rem;'>— {$label}</span>" : '') . "{$auto}</div>");
                                                })
                                                ->columnSpanFull(),
                                            Placeholder::make('payload_preview')
                                                ->label(__('admin.blog_category.fields.jsonld_payload_preview'))
                                                ->content(function ($record): HtmlString {
                                                    if (! $record || empty($record->payload)) {
                                                        return new HtmlString('<em class="text-gray-400">No payload yet — save to generate.</em>');
                                                    }
                                                    $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                                    return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">' . e($json) . '</pre>');
                                                })
                                                ->columnSpanFull(),
                                            Forms\Components\Toggle::make('is_active')
                                                ->label(__('admin.blog_category.fields.jsonld_active'))
                                                ->inline(false),
                                            Placeholder::make('schema_updated_at')
                                                ->label(__('admin.blog_category.fields.jsonld_last_generated'))
                                                ->content(fn ($record) => $record?->updated_at
                                                    ? $record->updated_at->diffForHumans() . ' (' . $record->updated_at->format('d/m/Y H:i') . ')'
                                                    : '—'
                                                ),
                                        ])
                                        ->itemLabel(fn (array $state): ?string =>
                                            filled($state['schema_type'] ?? '')
                                                ? (is_object($state['schema_type']) ? $state['schema_type']->value : (string) $state['schema_type'])
                                                : null
                                        )
                                        ->collapsed()
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->defaultItems(0)
                                        ->columnSpanFull(),

                                    \Filament\Schemas\Components\Actions::make([
                                        \Filament\Actions\Action::make('regenerate_jsonld_vi')
                                            ->label(__('admin.blog_category.actions.regenerate_jsonld_vi'))
                                            ->icon('heroicon-o-arrow-path')
                                            ->color('gray')
                                            ->requiresConfirmation()
                                            ->modalHeading(__('admin.blog_category.actions.regenerate_jsonld_vi_modal_heading'))
                                            ->modalDescription(__('admin.blog_category.actions.regenerate_jsonld_vi_modal_description'))
                                            ->action(function ($livewire): void {
                                                $category = $livewire->record;
                                                if (! $category?->exists) { return; }
                                                app(\App\Services\Seo\JsonldService::class)->syncForModel($category, 'vi');
                                                Notification::make()->title(__('admin.blog_category.notifications.jsonld_regenerated_vi'))->success()->send();
                                                redirect(BlogCategoryResource::getUrl('edit', ['record' => $category]));
                                            }),
                                    ]),
                                ]),

                            Tabs\Tab::make(__('admin.blog_category.tabs.locale_en'))
                                ->schema([
                                    Forms\Components\Repeater::make('jsonldSchemasEn')
                                        ->relationship()
                                        ->label(__('admin.blog_category.fields.jsonld_schemas_en'))
                                        ->schema([
                                            Placeholder::make('schema_header')
                                                ->label('')
                                                ->content(function ($record): HtmlString {
                                                    if (! $record) { return new HtmlString(''); }
                                                    $type  = $record->schema_type?->value ?? '—';
                                                    $label = e($record->label ?? '');
                                                    $auto  = $record->is_auto_generated
                                                        ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#fef9c3;color:#854d0e;">⚡ Auto</span>'
                                                        : '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;">✎ Manual</span>';
                                                    return new HtmlString("<div style='display:flex;align-items:center;gap:10px;flex-wrap:wrap;'><span style='font-weight:700;font-size:0.95rem;color:#1e293b;'>{$type}</span>" . (filled($label) ? "<span style='color:#64748b;font-size:0.85rem;'>— {$label}</span>" : '') . "{$auto}</div>");
                                                })
                                                ->columnSpanFull(),
                                            Placeholder::make('payload_preview')
                                                ->label(__('admin.blog_category.fields.jsonld_payload_preview'))
                                                ->content(function ($record): HtmlString {
                                                    if (! $record || empty($record->payload)) {
                                                        return new HtmlString('<em class="text-gray-400">No payload yet — save to generate.</em>');
                                                    }
                                                    $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                                    return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">' . e($json) . '</pre>');
                                                })
                                                ->columnSpanFull(),
                                            Forms\Components\Toggle::make('is_active')
                                                ->label(__('admin.blog_category.fields.jsonld_active'))
                                                ->inline(false),
                                            Placeholder::make('schema_updated_at')
                                                ->label(__('admin.blog_category.fields.jsonld_last_generated'))
                                                ->content(fn ($record) => $record?->updated_at
                                                    ? $record->updated_at->diffForHumans() . ' (' . $record->updated_at->format('d/m/Y H:i') . ')'
                                                    : '—'
                                                ),
                                        ])
                                        ->itemLabel(fn (array $state): ?string =>
                                            filled($state['schema_type'] ?? '')
                                                ? (is_object($state['schema_type']) ? $state['schema_type']->value : (string) $state['schema_type'])
                                                : null
                                        )
                                        ->collapsed()
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->defaultItems(0)
                                        ->columnSpanFull(),

                                    \Filament\Schemas\Components\Actions::make([
                                        \Filament\Actions\Action::make('regenerate_jsonld_en')
                                            ->label(__('admin.blog_category.actions.regenerate_jsonld_en'))
                                            ->icon('heroicon-o-arrow-path')
                                            ->color('gray')
                                            ->requiresConfirmation()
                                            ->modalHeading(__('admin.blog_category.actions.regenerate_jsonld_en_modal_heading'))
                                            ->modalDescription(__('admin.blog_category.actions.regenerate_jsonld_en_modal_description'))
                                            ->action(function ($livewire): void {
                                                $category = $livewire->record;
                                                if (! $category?->exists) { return; }
                                                app(\App\Services\Seo\JsonldService::class)->syncForModel($category, 'en');
                                                Notification::make()->title(__('admin.blog_category.notifications.jsonld_regenerated_en'))->success()->send();
                                                redirect(BlogCategoryResource::getUrl('edit', ['record' => $category]));
                                            }),
                                    ]),
                                ]),
                        ])
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed()
                ->columnSpanFull()
                ->hidden(fn ($record) => $record === null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('posts'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('posts_count')
                    ->label(__('admin.blog_category.fields.posts_count'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.blog_category.fields.active')),
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

    private static function charCounterColor(?string $state, int $min, int $max): string
    {
        $len = mb_strlen($state ?? '');
        if ($len === 0) return 'gray';
        if ($len < $min || $len > $max) return 'warning';
        return 'success';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogCategories::route('/'),
            'create' => Pages\CreateBlogCategory::route('/create'),
            'edit'   => Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
