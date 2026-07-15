<?php

namespace App\Filament\Resources;

use App\Enums\BlogPostStatus;
use App\Filament\Resources\BlogPostResource\Pages;
use App\Forms\Components\MediaFileUpload;
use App\Forms\Plugins\MediaRichEditorPlugin;
use App\Models\BlogPost;
use App\Services\Seo\JsonldService;
use App\Services\Seo\LlmsGeneratorService;
use App\Support\LocaleUrl;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
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
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Blog';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->tabs([

                    // ── Tab 1: General ────────────────────────────────────────
                    Tab::make(__('admin.blog_post.tabs.general'))
                        ->schema([
                            Forms\Components\Select::make('blog_category_id')
                                ->label(__('admin.blog_post.fields.category'))
                                ->relationship('blogCategory', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText(__('admin.blog_post.fields.category_help')),

                            MediaFileUpload::make('featured_image')
                                ->label(__('admin.blog_post.fields.featured_image'))
                                ->image()
                                ->nullable()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('tags')
                                ->label(__('admin.blog_post.fields.tags'))
                                ->relationship('tags', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))
                                        ),
                                    Forms\Components\TextInput::make('slug')
                                        ->required(),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    // ── Tab 2: Content ────────────────────────────────────────
                    Tab::make(__('admin.blog_post.tabs.content'))
                        ->icon('heroicon-o-language')
                        ->schema([
                            Tabs::make('LocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.blog_post.tabs.locale_vi_full'))
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.vi.title')
                                                ->label(__('admin.blog_post.fields.title_vi'))
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('translations.vi.slug', Str::slug($state ?? '')))
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.vi.slug')
                                                ->label(__('admin.blog_post.fields.slug_vi'))
                                                ->helperText(__('admin.blog_post.fields.slug_auto_help'))
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.vi.excerpt')
                                                ->label(__('admin.blog_post.fields.excerpt_vi'))
                                                ->rows(3)
                                                ->columnSpanFull(),

                                            Forms\Components\RichEditor::make('translations.vi.body')
                                                ->label(__('admin.blog_post.fields.body_vi'))
                                                ->plugins([MediaRichEditorPlugin::make()])
                                                ->columnSpanFull(),
                                        ]),

                                    Tab::make(__('admin.blog_post.tabs.locale_en_full'))
                                        ->schema([
                                            Forms\Components\TextInput::make('translations.en.title')
                                                ->label(__('admin.blog_post.fields.title_en'))
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($state, Set $set) => $set('translations.en.slug', Str::slug($state ?? '')))
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('translations.en.slug')
                                                ->label(__('admin.blog_post.fields.slug_en'))
                                                ->helperText(__('admin.blog_post.fields.slug_auto_help'))
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('translations.en.excerpt')
                                                ->label(__('admin.blog_post.fields.excerpt_en'))
                                                ->rows(3)
                                                ->columnSpanFull(),

                                            Forms\Components\RichEditor::make('translations.en.body')
                                                ->label(__('admin.blog_post.fields.body_en'))
                                                ->plugins([MediaRichEditorPlugin::make()])
                                                ->columnSpanFull(),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 3: Publishing ─────────────────────────────────────
                    Tab::make(__('admin.blog_post.tabs.publishing'))
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options(collect(BlogPostStatus::cases())->mapWithKeys(
                                    fn (BlogPostStatus $case) => [$case->value => ucfirst($case->value)]
                                ))
                                ->required()
                                ->default(BlogPostStatus::Draft->value),

                            Forms\Components\DateTimePicker::make('published_at')
                                ->nullable(),

                            Forms\Components\Select::make('author_id')
                                ->label(__('admin.blog_post.fields.author'))
                                ->relationship('author', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false)
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))
                                        ),
                                    Forms\Components\TextInput::make('slug')
                                        ->required(),
                                    Forms\Components\TextInput::make('title')
                                        ->label(__('admin.blog_post.fields.author_job_title')),
                                ])
                                ->placeholder(__('admin.blog_post.fields.author_placeholder')),
                        ])
                        ->columns(2),

                    // ── Tab 3: FAQ ────────────────────────────────────────────
                    Tab::make(__('admin.blog_post.tabs.faq'))
                        ->icon('heroicon-o-question-mark-circle')
                        ->schema([
                            Placeholder::make('faq_hint')
                                ->label('')
                                ->content(new HtmlString(
                                    '<p class="text-sm text-gray-500">'
                                    .'FAQ items are automatically injected into the <strong>FAQPage JSON-LD schema</strong> '
                                    .'and the <strong>LLMs document</strong> when the post is published.'
                                    .'</p>'
                                ))
                                ->columnSpanFull(),

                            Section::make(__('admin.blog_post.tabs.locale_vi'))
                                ->schema([
                                    Forms\Components\Repeater::make('faq_items_vi')
                                        ->label('')
                                        ->schema([
                                            Forms\Components\TextInput::make('question')
                                                ->label(__('admin.blog_post.fields.faq_question'))
                                                ->required()
                                                ->placeholder(__('admin.blog_post.fields.faq_question_vi_placeholder'))
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('answer')
                                                ->label(__('admin.blog_post.fields.faq_answer'))
                                                ->required()
                                                ->rows(3)
                                                ->columnSpanFull(),
                                        ])
                                        ->afterStateHydrated(function (Forms\Components\Repeater $component, $record): void {
                                            $component->state($record?->faq_items_vi ?? []);
                                        })
                                        ->addActionLabel(__('admin.blog_post.actions.add_faq'))
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),

                            Section::make(__('admin.blog_post.tabs.locale_en'))
                                ->schema([
                                    Forms\Components\Repeater::make('faq_items_en')
                                        ->label('')
                                        ->schema([
                                            Forms\Components\TextInput::make('question')
                                                ->label(__('admin.blog_post.fields.faq_question'))
                                                ->required()
                                                ->placeholder(__('admin.blog_post.fields.faq_question_en_placeholder'))
                                                ->columnSpanFull(),

                                            Forms\Components\Textarea::make('answer')
                                                ->label(__('admin.blog_post.fields.faq_answer'))
                                                ->required()
                                                ->rows(3)
                                                ->columnSpanFull(),
                                        ])
                                        ->afterStateHydrated(function (Forms\Components\Repeater $component, $record): void {
                                            $component->state($record?->faq_items_en ?? []);
                                        })
                                        ->addActionLabel(__('admin.blog_post.actions.add_faq'))
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 3b: GEO / AI ──────────────────────────────────────
                    Tab::make(__('admin.blog_post.tabs.geo_ai'))
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Placeholder::make('geo_hint')
                                ->label('')
                                ->content(new HtmlString(
                                    '<p class="text-sm text-gray-500">'
                                    .'AI Summary is rendered <strong>answer-first</strong> on the public post page '
                                    .'(right under the title) and read by AI Overviews / ChatGPT / Perplexity crawlers. '
                                    .'Key Facts render as a quick-facts list under it. Both also feed the LLMs document.'
                                    .'</p>'
                                ))
                                ->columnSpanFull(),

                            Tabs::make('GeoLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.blog_post.tabs.locale_vi'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileVi')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')->default('vi'),

                                                    Forms\Components\Textarea::make('ai_summary')
                                                        ->label(__('admin.blog_post.fields.ai_summary_vi'))
                                                        ->hint(__('admin.blog_post.fields.ai_summary_hint'))
                                                        ->rows(4)
                                                        ->placeholder(__('admin.blog_post.fields.ai_summary_vi_placeholder'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('use_cases')
                                                        ->label(__('admin.blog_post.fields.use_cases_vi'))
                                                        ->hint(__('admin.blog_post.fields.use_cases_hint'))
                                                        ->rows(3)
                                                        ->columnSpanFull(),

                                                    Forms\Components\TextInput::make('target_audience')
                                                        ->label(__('admin.blog_post.fields.target_audience_vi'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('llm_context_hint')
                                                        ->label(__('admin.blog_post.fields.llm_context_hint_vi'))
                                                        ->rows(2)
                                                        ->columnSpanFull(),

                                                    Forms\Components\Repeater::make('key_facts')
                                                        ->label(__('admin.blog_post.fields.key_facts_vi'))
                                                        ->hint(__('admin.blog_post.fields.key_facts_hint'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('label')
                                                                ->label(__('admin.blog_post.fields.key_fact_label'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_post.fields.key_fact_label_placeholder_vi')),
                                                            Forms\Components\TextInput::make('value')
                                                                ->label(__('admin.blog_post.fields.key_fact_value'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_post.fields.key_fact_value_placeholder_vi')),
                                                        ])
                                                        ->columns(2)
                                                        ->addActionLabel(__('admin.blog_post.actions.add_key_fact'))
                                                        ->reorderable()
                                                        ->collapsible()
                                                        ->defaultItems(0)
                                                        ->columnSpanFull(),
                                                ]),
                                        ]),

                                    Tab::make(__('admin.blog_post.tabs.locale_en'))
                                        ->schema([
                                            Group::make()
                                                ->relationship('geoProfileEn')
                                                ->schema([
                                                    Forms\Components\Hidden::make('locale')->default('en'),

                                                    Forms\Components\Textarea::make('ai_summary')
                                                        ->label(__('admin.blog_post.fields.ai_summary_en'))
                                                        ->hint(__('admin.blog_post.fields.ai_summary_hint'))
                                                        ->rows(4)
                                                        ->placeholder(__('admin.blog_post.fields.ai_summary_en_placeholder'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('use_cases')
                                                        ->label(__('admin.blog_post.fields.use_cases_en'))
                                                        ->hint(__('admin.blog_post.fields.use_cases_hint'))
                                                        ->rows(3)
                                                        ->columnSpanFull(),

                                                    Forms\Components\TextInput::make('target_audience')
                                                        ->label(__('admin.blog_post.fields.target_audience_en'))
                                                        ->columnSpanFull(),

                                                    Forms\Components\Textarea::make('llm_context_hint')
                                                        ->label(__('admin.blog_post.fields.llm_context_hint_en'))
                                                        ->rows(2)
                                                        ->columnSpanFull(),

                                                    Forms\Components\Repeater::make('key_facts')
                                                        ->label(__('admin.blog_post.fields.key_facts_en'))
                                                        ->hint(__('admin.blog_post.fields.key_facts_hint'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('label')
                                                                ->label(__('admin.blog_post.fields.key_fact_label'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_post.fields.key_fact_label_placeholder_en')),
                                                            Forms\Components\TextInput::make('value')
                                                                ->label(__('admin.blog_post.fields.key_fact_value'))
                                                                ->required()
                                                                ->placeholder(__('admin.blog_post.fields.key_fact_value_placeholder_en')),
                                                        ])
                                                        ->columns(2)
                                                        ->addActionLabel(__('admin.blog_post.actions.add_key_fact'))
                                                        ->reorderable()
                                                        ->collapsible()
                                                        ->defaultItems(0)
                                                        ->columnSpanFull(),
                                                ]),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ── Tab 4: SEO ───────────────────────────────────────────
                    Tab::make(__('admin.blog_post.tabs.seo'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Tabs::make('SeoLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.blog_post.tabs.locale_vi'))
                                        ->schema([
                                            Placeholder::make('canonical_url_auto_vi')
                                                ->label(__('admin.blog_post.fields.canonical_url_auto'))
                                                ->content(function ($record): string {
                                                    $slug = $record?->translations()->where('locale', 'vi')->value('slug');
                                                    if (! $slug) {
                                                        return '—';
                                                    }
                                                    $record->loadMissing(['blogCategory.translations']);

                                                    return LocaleUrl::forBlogPost($record, 'vi') ?: '—';
                                                }),

                                            Group::make()
                                                ->relationship('seoMetaVi')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'vi', ...$data]
                                                )
                                                ->schema([
                                                    Section::make(__('admin.blog_post.sections.meta_tags'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(__('admin.blog_post.fields.meta_title_vi'))
                                                                ->placeholder(__('admin.blog_post.fields.meta_title_placeholder'))
                                                                ->helperText(__('admin.blog_post.fields.meta_title_help'))
                                                                ->live(debounce: 500)
                                                                ->hint(fn ($state): string => mb_strlen($state ?? '').'/60')
                                                                ->hintColor(fn ($state): string => mb_strlen($state ?? '') > 60 ? 'warning' : 'success')
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('meta_description')
                                                                ->label(__('admin.blog_post.fields.meta_description_vi'))
                                                                ->placeholder(__('admin.blog_post.fields.meta_description_placeholder'))
                                                                ->helperText(__('admin.blog_post.fields.meta_description_help'))
                                                                ->rows(3)
                                                                ->live(debounce: 500)
                                                                ->hint(fn ($state): string => mb_strlen($state ?? '').'/155')
                                                                ->hintColor(fn ($state): string => mb_strlen($state ?? '') > 155 ? 'warning' : 'success')
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(__('admin.blog_post.fields.canonical_url_override'))
                                                                ->url()
                                                                ->placeholder(__('admin.blog_post.fields.canonical_url_override_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('robots')
                                                                ->label(__('admin.blog_post.fields.robots_vi'))
                                                                ->options([
                                                                    'index,follow' => 'index, follow — Default',
                                                                    'noindex,follow' => 'noindex, follow — Exclude from index',
                                                                    'noindex,nofollow' => 'noindex, nofollow — Block completely',
                                                                ])
                                                                ->default('index,follow')
                                                                ->native(false),
                                                        ])
                                                        ->columns(2),

                                                    Section::make(__('admin.blog_post.sections.og_vi'))
                                                        ->schema([
                                                            MediaFileUpload::make('og_image')
                                                                ->label(__('admin.blog_post.fields.og_image_vi'))
                                                                ->helperText(__('admin.blog_post.fields.og_image_help'))
                                                                ->image()
                                                                ->nullable()
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(__('admin.blog_post.fields.og_title_vi'))
                                                                ->placeholder(__('admin.blog_post.fields.auto_from_meta_title'))
                                                                ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record): void {
                                                                    if (blank($state)) {
                                                                        $component->state($record?->meta_title);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('og_description')
                                                                ->label(__('admin.blog_post.fields.og_description_vi'))
                                                                ->rows(2)
                                                                ->afterStateHydrated(function (Forms\Components\Textarea $component, $state, $record): void {
                                                                    if (blank($state)) {
                                                                        $component->state($record?->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsible()
                                                        ->collapsed(),
                                                ]),
                                        ]),

                                    Tab::make(__('admin.blog_post.tabs.locale_en'))
                                        ->schema([
                                            Placeholder::make('canonical_url_auto_en')
                                                ->label(__('admin.blog_post.fields.canonical_url_auto'))
                                                ->content(function ($record): string {
                                                    $slug = $record?->translations()->where('locale', 'en')->value('slug');
                                                    if (! $slug) {
                                                        return '—';
                                                    }
                                                    $record->loadMissing(['blogCategory.translations']);

                                                    return LocaleUrl::forBlogPost($record, 'en') ?: '—';
                                                }),

                                            Group::make()
                                                ->relationship('seoMetaEn')
                                                ->mutateRelationshipDataBeforeCreateUsing(
                                                    fn (array $data) => ['locale' => 'en', ...$data]
                                                )
                                                ->schema([
                                                    Section::make(__('admin.blog_post.sections.meta_tags'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('meta_title')
                                                                ->label(__('admin.blog_post.fields.meta_title_en'))
                                                                ->placeholder(__('admin.blog_post.fields.meta_title_placeholder'))
                                                                ->helperText(__('admin.blog_post.fields.meta_title_help'))
                                                                ->live(debounce: 500)
                                                                ->hint(fn ($state): string => mb_strlen($state ?? '').'/60')
                                                                ->hintColor(fn ($state): string => mb_strlen($state ?? '') > 60 ? 'warning' : 'success')
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('meta_description')
                                                                ->label(__('admin.blog_post.fields.meta_description_en'))
                                                                ->placeholder(__('admin.blog_post.fields.meta_description_placeholder'))
                                                                ->helperText(__('admin.blog_post.fields.meta_description_help'))
                                                                ->rows(3)
                                                                ->live(debounce: 500)
                                                                ->hint(fn ($state): string => mb_strlen($state ?? '').'/155')
                                                                ->hintColor(fn ($state): string => mb_strlen($state ?? '') > 155 ? 'warning' : 'success')
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('canonical_url')
                                                                ->label(__('admin.blog_post.fields.canonical_url_override'))
                                                                ->url()
                                                                ->placeholder(__('admin.blog_post.fields.canonical_url_override_placeholder'))
                                                                ->columnSpanFull(),

                                                            Forms\Components\Select::make('robots')
                                                                ->label(__('admin.blog_post.fields.robots_en'))
                                                                ->options([
                                                                    'index,follow' => 'index, follow — Default',
                                                                    'noindex,follow' => 'noindex, follow — Exclude from index',
                                                                    'noindex,nofollow' => 'noindex, nofollow — Block completely',
                                                                ])
                                                                ->default('index,follow')
                                                                ->native(false),
                                                        ])
                                                        ->columns(2),

                                                    Section::make(__('admin.blog_post.sections.og_en'))
                                                        ->schema([
                                                            MediaFileUpload::make('og_image')
                                                                ->label(__('admin.blog_post.fields.og_image_en'))
                                                                ->helperText(__('admin.blog_post.fields.og_image_help'))
                                                                ->image()
                                                                ->nullable()
                                                                ->columnSpanFull(),

                                                            Forms\Components\TextInput::make('og_title')
                                                                ->label(__('admin.blog_post.fields.og_title_en'))
                                                                ->placeholder(__('admin.blog_post.fields.auto_from_meta_title'))
                                                                ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record): void {
                                                                    if (blank($state)) {
                                                                        $component->state($record?->meta_title);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),

                                                            Forms\Components\Textarea::make('og_description')
                                                                ->label(__('admin.blog_post.fields.og_description_en'))
                                                                ->rows(2)
                                                                ->afterStateHydrated(function (Forms\Components\Textarea $component, $state, $record): void {
                                                                    if (blank($state)) {
                                                                        $component->state($record?->meta_description);
                                                                    }
                                                                })
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->columns(2)
                                                        ->collapsible()
                                                        ->collapsed(),
                                                ]),
                                        ]),
                                ]),
                        ]),

                    // ── Tab 5: JSON-LD ────────────────────────────────────────
                    Tab::make(__('admin.blog_post.tabs.jsonld'))
                        ->icon('heroicon-o-code-bracket')
                        ->schema([

                            Section::make(__('admin.blog_post.sections.jsonld_how_it_works'))
                                ->schema([
                                    Placeholder::make('jsonld_info')
                                        ->label('')
                                        ->content(new HtmlString('
                                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                <li>Schemas marked <strong>Auto</strong> are regenerated every time this post is saved — do not manually edit their payload here.</li>
                                                <li>To customize a payload, go to <strong>SEO &amp; GEO → JSON-LD Schemas</strong> and set <em>Auto Generated = off</em> first.</li>
                                                <li>Toggle <strong>Active</strong> to include / exclude a schema from the page <code>&lt;head&gt;</code>.</li>
                                                <li>Schemas are only generated for <strong>Published</strong> posts.</li>
                                            </ul>
                                        '))
                                        ->columnSpanFull(),
                                ])
                                ->collapsed()
                                ->collapsible(),

                            Tabs::make('JsonldLocaleTabs')
                                ->tabs([
                                    Tab::make(__('admin.blog_post.tabs.locale_vi'))
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasVi')
                                                ->relationship()
                                                ->label(__('admin.blog_post.fields.jsonld_schemas_vi'))
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
                                                        ->label(__('admin.blog_post.fields.jsonld_payload_preview'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">No payload yet — publish the post to generate.</em>');
                                                            }
                                                            $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                                            return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">'.e($json).'</pre>');
                                                        })
                                                        ->columnSpanFull(),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(__('admin.blog_post.fields.jsonld_active'))
                                                        ->inline(false),
                                                    Placeholder::make('schema_updated_at')
                                                        ->label(__('admin.blog_post.fields.jsonld_last_generated'))
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
                                                    ->label(__('admin.blog_post.actions.regenerate_jsonld_vi'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading(__('admin.blog_post.actions.regenerate_jsonld_vi_modal_heading'))
                                                    ->modalDescription(__('admin.blog_post.actions.regenerate_jsonld_vi_modal_description'))
                                                    ->action(function ($livewire): void {
                                                        $post = $livewire->record;
                                                        if (! $post?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($post, 'vi');
                                                        Notification::make()->title(__('admin.blog_post.notifications.jsonld_regenerated_vi'))->success()->send();
                                                        redirect(BlogPostResource::getUrl('edit', ['record' => $post]));
                                                    }),
                                            ]),
                                        ]),

                                    Tab::make(__('admin.blog_post.tabs.locale_en'))
                                        ->schema([
                                            Forms\Components\Repeater::make('jsonldSchemasEn')
                                                ->relationship()
                                                ->label(__('admin.blog_post.fields.jsonld_schemas_en'))
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
                                                        ->label(__('admin.blog_post.fields.jsonld_payload_preview'))
                                                        ->content(function ($record): HtmlString {
                                                            if (! $record || empty($record->payload)) {
                                                                return new HtmlString('<em class="text-gray-400">No payload yet — publish the post to generate.</em>');
                                                            }
                                                            $json = json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                                            return new HtmlString('<pre style="white-space:pre-wrap;font-size:0.75rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;">'.e($json).'</pre>');
                                                        })
                                                        ->columnSpanFull(),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->label(__('admin.blog_post.fields.jsonld_active'))
                                                        ->inline(false),
                                                    Placeholder::make('schema_updated_at')
                                                        ->label(__('admin.blog_post.fields.jsonld_last_generated'))
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
                                                    ->label(__('admin.blog_post.actions.regenerate_jsonld_en'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->requiresConfirmation()
                                                    ->modalHeading(__('admin.blog_post.actions.regenerate_jsonld_en_modal_heading'))
                                                    ->modalDescription(__('admin.blog_post.actions.regenerate_jsonld_en_modal_description'))
                                                    ->action(function ($livewire): void {
                                                        $post = $livewire->record;
                                                        if (! $post?->exists) {
                                                            return;
                                                        }
                                                        app(JsonldService::class)->syncForModel($post, 'en');
                                                        Notification::make()->title(__('admin.blog_post.notifications.jsonld_regenerated_en'))->success()->send();
                                                        redirect(BlogPostResource::getUrl('edit', ['record' => $post]));
                                                    }),
                                            ]),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->hidden(fn ($record) => $record === null),

                    // ── Tab 6: LLMs ───────────────────────────────────────────
                    Tab::make(__('admin.blog_post.tabs.llms'))
                        ->icon('heroicon-o-document-text')
                        ->schema([

                            Section::make(__('admin.blog_post.sections.llms_how_it_works'))
                                ->schema([
                                    Placeholder::make('llms_source_hint')
                                        ->label('')
                                        ->content(new HtmlString('
                                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                <li>Content is <strong>auto-assembled</strong> from the <strong>FAQ</strong> tab and GEO profile when this post is saved.</li>
                                                <li>To change the output — edit the <strong>FAQ</strong> tab or add a GEO profile, not here.</li>
                                                <li>Use <strong>Regenerate</strong> below to force a re-sync without re-saving the post.</li>
                                                <li>Toggle <strong>Published</strong> to include / exclude from the AI document file.</li>
                                                <li>Entries are only generated for <strong>Published</strong> posts.</li>
                                            </ul>
                                        '))
                                        ->columnSpanFull(),
                                ])
                                ->collapsed()
                                ->collapsible(),

                            Forms\Components\Repeater::make('llmsEntries')
                                ->relationship()
                                ->label(__('admin.blog_post.fields.llms_published_entries'))
                                ->schema([

                                    Placeholder::make('llms_preview')
                                        ->label(__('admin.blog_post.fields.llms_preview'))
                                        ->content(function ($record): HtmlString {
                                            if (! $record) {
                                                return new HtmlString('<em class="text-gray-400">Not generated yet — publish the post to trigger sync.</em>');
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

                                            $content = implode("\n", $lines);

                                            return new HtmlString(
                                                '<pre style="white-space:pre-wrap;font-size:0.8rem;line-height:1.6;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;color:#334155;">'
                                                .$content
                                                .'</pre>'
                                            );
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('admin.blog_post.fields.llms_published'))
                                        ->helperText(__('admin.blog_post.fields.llms_published_help'))
                                        ->inline(false),

                                    Placeholder::make('updated_at')
                                        ->label(__('admin.blog_post.fields.last_synced'))
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
                                Action::make('regenerate_llms')
                                    ->label(__('admin.blog_post.actions.regenerate_llms'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('gray')
                                    ->requiresConfirmation()
                                    ->modalHeading(__('admin.blog_post.actions.regenerate_llms_modal_heading'))
                                    ->modalDescription(__('admin.blog_post.actions.regenerate_llms_modal_description'))
                                    ->action(function ($livewire): void {
                                        $post = $livewire->record;

                                        if (! $post?->exists) {
                                            return;
                                        }

                                        app(LlmsGeneratorService::class)->upsertEntry($post);

                                        Notification::make()
                                            ->title(__('admin.blog_post.notifications.llms_regenerated_title'))
                                            ->body(__('admin.blog_post.notifications.llms_regenerated_body'))
                                            ->success()
                                            ->send();

                                        redirect(BlogPostResource::getUrl('edit', ['record' => $post]));
                                    }),
                            ]),
                        ])
                        ->hidden(fn ($record) => $record === null),

                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title_bilingual')
                    ->label(__('admin.blog_post.fields.title'))
                    ->html()
                    ->getStateUsing(function ($record): string {
                        $vi = $record->translations->firstWhere('locale', 'vi')?->title;
                        $en = $record->translations->firstWhere('locale', 'en')?->title;
                        $top = e($vi ?? '—');
                        $bottom = $en ? '<br><span style="font-size:0.75rem;color:#6b7280">'.e($en).'</span>' : '';

                        return $top.$bottom;
                    })
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('translations', fn ($q) => $q->where('title', 'ilike', "%{$search}%"));
                    })
                    ->wrap(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => BlogPostStatus::Draft->value,
                        'success' => BlogPostStatus::Published->value,
                        'danger' => BlogPostStatus::Archived->value,
                    ]),

                Tables\Columns\TextColumn::make('blogCategory.name')
                    ->label(__('admin.blog_post.fields.category'))
                    ->placeholder(__('admin.blog_post.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('author.name')
                    ->label(__('admin.blog_post.fields.author'))
                    ->placeholder(__('admin.blog_post.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(timezone: 'Asia/Ho_Chi_Minh')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(BlogPostStatus::cases())->mapWithKeys(
                        fn (BlogPostStatus $case) => [$case->value => ucfirst($case->value)]
                    )),

                Tables\Filters\SelectFilter::make('blog_category_id')
                    ->label(__('admin.blog_post.fields.category'))
                    ->relationship('blogCategory', 'name'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
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
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
