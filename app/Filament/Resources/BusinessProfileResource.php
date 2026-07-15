<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessProfileResource\Pages;
use App\Models\BusinessProfile;
use App\Models\Seo\LlmsDocument;
use App\Services\Seo\BusinessJsonldService;
use App\Services\Seo\LlmsGeneratorService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class BusinessProfileResource extends Resource
{
    protected static ?string $model = BusinessProfile::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static \UnitEnum|string|null $navigationGroup = 'Setting';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Business Profile';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->persistTabInQueryString()
                ->tabs([

                    // ── Identity ──────────────────────────────────────────────
                    Tab::make(__('admin.business_profile.tabs.identity'))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('admin.business_profile.fields.name'))
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('legal_name')
                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.legal_name_vi').'</span>'))
                                ->placeholder(__('admin.business_profile.fields.legal_name_vi_placeholder')),

                            Forms\Components\TextInput::make('extra.legal_name_en')
                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.legal_name_en').'</span>'))
                                ->placeholder(__('admin.business_profile.fields.legal_name_en_placeholder')),

                            Forms\Components\TextInput::make('tagline')
                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.tagline_vi').'</span>'))
                                ->placeholder(__('admin.business_profile.fields.tagline_vi_placeholder')),

                            Forms\Components\TextInput::make('extra.tagline_en')
                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.tagline_en').'</span>'))
                                ->placeholder(__('admin.business_profile.fields.tagline_en_placeholder')),

                            Forms\Components\Textarea::make('description')
                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.description_vi').'</span>'))
                                ->rows(4)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('extra.description_en')
                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.description_en').'</span>'))
                                ->rows(4)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('logo_path')
                                ->label(__('admin.business_profile.fields.logo_path'))
                                ->columnSpanFull(),

                            Forms\Components\FileUpload::make('extra.og_image')
                                ->label(__('admin.business_profile.fields.og_image'))
                                ->helperText(__('admin.business_profile.fields.og_image_help'))
                                ->image()
                                ->disk('public')
                                ->directory('og')
                                ->imagePreviewHeight('120')
                                ->columnSpanFull(),

                            Forms\Components\FileUpload::make('extra.favicon')
                                ->label(__('admin.business_profile.fields.favicon'))
                                ->helperText(__('admin.business_profile.fields.favicon_help'))
                                ->image()
                                ->disk('public')
                                ->directory('favicon')
                                ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/gif'])
                                ->imagePreviewHeight('80')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('currency')
                                ->label(__('admin.business_profile.fields.currency'))
                                ->default('VND'),

                            Forms\Components\TextInput::make('founded_year')
                                ->label(__('admin.business_profile.fields.founded_year'))
                                ->numeric()
                                ->minValue(1800)
                                ->maxValue(now()->year),

                            Forms\Components\TextInput::make('vat_number')
                                ->label(__('admin.business_profile.fields.vat_number')),
                        ])
                        ->columns(2),

                    // ── Contact ───────────────────────────────────────────────
                    Tab::make(__('admin.business_profile.tabs.contact'))
                        ->schema([
                            Forms\Components\TextInput::make('email')
                                ->label(__('admin.business_profile.fields.email'))
                                ->email(),

                            Forms\Components\TextInput::make('phone')
                                ->label(__('admin.business_profile.fields.phone'))
                                ->tel(),

                            Forms\Components\Textarea::make('address_line')
                                ->label(__('admin.business_profile.fields.address_line'))
                                ->rows(2)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('city')
                                ->label(__('admin.business_profile.fields.city')),

                            Forms\Components\TextInput::make('state')
                                ->label(__('admin.business_profile.fields.state')),

                            Forms\Components\Select::make('country')
                                ->label(__('admin.business_profile.fields.country'))
                                ->helperText(__('admin.business_profile.fields.country_help'))
                                ->options(config('countries'))
                                ->searchable()
                                ->default('VN'),

                            Forms\Components\TextInput::make('postal_code')
                                ->label(__('admin.business_profile.fields.postal_code')),

                            Forms\Components\TextInput::make('latitude')
                                ->label(__('admin.business_profile.fields.latitude'))
                                ->numeric()
                                ->placeholder(__('admin.business_profile.fields.latitude_placeholder')),

                            Forms\Components\TextInput::make('longitude')
                                ->label(__('admin.business_profile.fields.longitude'))
                                ->numeric()
                                ->placeholder(__('admin.business_profile.fields.longitude_placeholder')),
                        ])
                        ->columns(2),

                    // ── Return policy ─────────────────────────────────────────
                    Tab::make(__('admin.business_profile.tabs.return_policy'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->schema([
                            Section::make(__('admin.business_profile.sections.return_policy'))
                                ->description(__('admin.business_profile.sections.return_policy_desc'))
                                ->schema([
                                    Forms\Components\TextInput::make('extra.return_days')
                                        ->label(__('admin.business_profile.fields.return_days'))
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(7)
                                        ->helperText(__('admin.business_profile.fields.return_days_help'))
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('extra.return_method')
                                        ->label(__('admin.business_profile.fields.return_method'))
                                        ->options([
                                            'mail' => 'Gửi qua bưu điện',
                                            'in_store' => 'Trả trực tiếp tại shop',
                                        ])
                                        ->default('mail')
                                        ->native(false)
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('extra.return_fees')
                                        ->label(__('admin.business_profile.fields.return_fees'))
                                        ->options([
                                            'free' => 'Miễn phí',
                                            'customer' => 'Khách chịu phí',
                                        ])
                                        ->default('customer')
                                        ->native(false)
                                        ->columnSpan(1),
                                ])
                                ->columns(3),
                        ]),

                    // ── Online ────────────────────────────────────────────────
                    Tab::make(__('admin.business_profile.tabs.online_presence'))
                        ->schema([
                            Section::make(__('admin.business_profile.sections.social_links'))
                                ->description(__('admin.business_profile.sections.social_links_desc'))
                                ->schema([
                                    Forms\Components\TextInput::make('social_links.facebook')
                                        ->label(__('admin.business_profile.fields.facebook'))
                                        ->url()
                                        ->placeholder(__('admin.business_profile.fields.facebook_placeholder')),

                                    Forms\Components\TextInput::make('social_links.instagram')
                                        ->label(__('admin.business_profile.fields.instagram'))
                                        ->url()
                                        ->placeholder(__('admin.business_profile.fields.instagram_placeholder')),

                                    Forms\Components\TextInput::make('social_links.youtube')
                                        ->label(__('admin.business_profile.fields.youtube'))
                                        ->url()
                                        ->placeholder(__('admin.business_profile.fields.youtube_placeholder')),

                                    Forms\Components\TextInput::make('social_links.tiktok')
                                        ->label(__('admin.business_profile.fields.tiktok'))
                                        ->url()
                                        ->placeholder(__('admin.business_profile.fields.tiktok_placeholder')),

                                    Forms\Components\TextInput::make('social_links.twitter')
                                        ->label(__('admin.business_profile.fields.twitter'))
                                        ->url()
                                        ->placeholder(__('admin.business_profile.fields.twitter_placeholder')),

                                    Forms\Components\TextInput::make('social_links.zalo')
                                        ->label(__('admin.business_profile.fields.zalo'))
                                        ->url()
                                        ->placeholder(__('admin.business_profile.fields.zalo_placeholder')),
                                ])
                                ->columns(2),

                            Section::make(__('admin.business_profile.sections.shipping_carriers'))
                                ->description(__('admin.business_profile.sections.shipping_carriers_desc'))
                                ->schema([
                                    Forms\Components\Repeater::make('extra.shipping_carriers')
                                        ->label('')
                                        ->schema([
                                            Forms\Components\FileUpload::make('logo')
                                                ->label(__('admin.business_profile.fields.logo'))
                                                ->image()
                                                ->disk('public')
                                                ->directory('shipping-carriers')
                                                ->imagePreviewHeight('60')
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('name')
                                                ->label(__('admin.business_profile.fields.carrier_name'))
                                                ->placeholder(__('admin.business_profile.fields.carrier_name_placeholder'))
                                                ->required()
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('url')
                                                ->label(__('admin.business_profile.fields.link'))
                                                ->url()
                                                ->placeholder(__('admin.business_profile.fields.carrier_url_placeholder'))
                                                ->columnSpan(1),
                                        ])
                                        ->columns(3)
                                        ->reorderable()
                                        ->reorderableWithDragAndDrop()
                                        ->addActionLabel(__('admin.business_profile.actions.add_carrier'))
                                        ->columnSpanFull(),
                                ]),

                            Section::make(__('admin.business_profile.sections.payment_methods'))
                                ->description(__('admin.business_profile.sections.payment_methods_desc'))
                                ->schema([
                                    Forms\Components\Repeater::make('extra.payment_methods')
                                        ->label('')
                                        ->schema([
                                            Forms\Components\FileUpload::make('logo')
                                                ->label(__('admin.business_profile.fields.logo'))
                                                ->image()
                                                ->disk('public')
                                                ->directory('payment-methods')
                                                ->imagePreviewHeight('60')
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('name')
                                                ->label(__('admin.business_profile.fields.payment_name'))
                                                ->placeholder(__('admin.business_profile.fields.payment_name_placeholder'))
                                                ->required()
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('url')
                                                ->label(__('admin.business_profile.fields.link'))
                                                ->url()
                                                ->placeholder(__('admin.business_profile.fields.payment_url_placeholder'))
                                                ->columnSpan(1),
                                        ])
                                        ->columns(3)
                                        ->reorderable()
                                        ->reorderableWithDragAndDrop()
                                        ->addActionLabel(__('admin.business_profile.actions.add_payment_method'))
                                        ->columnSpanFull(),
                                ]),

                            Forms\Components\Repeater::make('business_hours')
                                ->label(__('admin.business_profile.fields.business_hours'))
                                ->helperText(__('admin.business_profile.fields.business_hours_help'))
                                ->schema([
                                    Forms\Components\Select::make('day')
                                        ->label(__('admin.business_profile.fields.day'))
                                        ->options([
                                            'Monday' => 'Monday',
                                            'Tuesday' => 'Tuesday',
                                            'Wednesday' => 'Wednesday',
                                            'Thursday' => 'Thursday',
                                            'Friday' => 'Friday',
                                            'Saturday' => 'Saturday',
                                            'Sunday' => 'Sunday',
                                        ])
                                        ->required()
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('open')
                                        ->label(__('admin.business_profile.fields.open'))
                                        ->placeholder(__('admin.business_profile.fields.open_placeholder'))
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('close')
                                        ->label(__('admin.business_profile.fields.close'))
                                        ->placeholder(__('admin.business_profile.fields.close_placeholder'))
                                        ->columnSpan(1),
                                ])
                                ->columns(3)
                                ->reorderable()
                                ->addActionLabel(__('admin.business_profile.actions.add_business_hour'))
                                ->columnSpanFull(),

                            Forms\Components\Repeater::make('extra.faq')
                                ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.business_profile.sections.faq_vi').'</span>'))
                                ->helperText(__('admin.business_profile.sections.faq_vi_help'))
                                ->schema([
                                    Forms\Components\TextInput::make('question')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.business_profile.fields.faq_question').'</span>'))
                                        ->required()
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('answer')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">'.__('admin.business_profile.fields.faq_answer').'</span>'))
                                        ->required()
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->reorderable()
                                ->addActionLabel(__('admin.business_profile.actions.add_faq'))
                                ->columnSpanFull(),

                            Forms\Components\Repeater::make('extra.faq_en')
                                ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.business_profile.sections.faq_en').'</span>'))
                                ->helperText(__('admin.business_profile.sections.faq_en_help'))
                                ->schema([
                                    Forms\Components\TextInput::make('question')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.business_profile.fields.faq_question').'</span>'))
                                        ->required()
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('answer')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">'.__('admin.business_profile.fields.faq_answer').'</span>'))
                                        ->required()
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->reorderable()
                                ->addActionLabel(__('admin.business_profile.actions.add_faq'))
                                ->columnSpanFull(),
                        ]),

                    // ── Page Fallbacks ────────────────────────────────────────
                    Tab::make(__('admin.business_profile.tabs.page_fallbacks'))
                        ->id('page-fallbacks')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Section::make(__('admin.business_profile.sections.page_fallback_home'))
                                ->description(__('admin.business_profile.sections.page_fallback_home_desc'))
                                ->schema([
                                    Forms\Components\TextInput::make('extra.home_title')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.home_title_vi').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.home_title_vi_placeholder')),

                                    Forms\Components\TextInput::make('extra.home_title_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.home_title_en').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.home_title_en_placeholder')),

                                    Forms\Components\Textarea::make('extra.meta_description')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.home_meta_description_vi').'</span>'))
                                        ->rows(2)
                                        ->placeholder(__('admin.business_profile.fields.home_meta_description_vi_placeholder')),

                                    Forms\Components\Textarea::make('extra.meta_description_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.home_meta_description_en').'</span>'))
                                        ->rows(2)
                                        ->placeholder(__('admin.business_profile.fields.home_meta_description_en_placeholder')),
                                ])
                                ->columns(2),

                            // Product Catalog (/cua-hang, /shop) — chuyển sang
                            // Pages Setting → Shop Setting (App\Filament\Pages\ShopSetting),
                            // vẫn đọc/ghi cùng key extra.product_catalog_*.

                            Section::make(__('admin.business_profile.sections.page_fallback_category_index'))
                                ->description(__('admin.business_profile.sections.page_fallback_category_index_desc'))
                                ->schema([
                                    Forms\Components\TextInput::make('extra.category_index_title')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.category_index_title_vi').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.category_index_title_vi_placeholder')),

                                    Forms\Components\TextInput::make('extra.category_index_title_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.category_index_title_en').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.category_index_title_en_placeholder')),

                                    Forms\Components\Textarea::make('extra.category_index_description')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.category_index_description_vi').'</span>'))
                                        ->rows(2)
                                        ->placeholder(__('admin.business_profile.fields.category_index_description_vi_placeholder')),

                                    Forms\Components\Textarea::make('extra.category_index_description_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.category_index_description_en').'</span>'))
                                        ->rows(2)
                                        ->placeholder(__('admin.business_profile.fields.category_index_description_en_placeholder')),
                                ])
                                ->columns(2),

                            Section::make(__('admin.business_profile.sections.page_fallback_about'))
                                ->description(__('admin.business_profile.sections.page_fallback_about_desc'))
                                ->schema([
                                    Forms\Components\TextInput::make('extra.about_title')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.about_title_vi').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.about_title_vi_placeholder')),

                                    Forms\Components\TextInput::make('extra.about_title_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.about_title_en').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.about_title_en_placeholder')),

                                    Forms\Components\Textarea::make('extra.about_meta_description')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.about_meta_description_vi').'</span>'))
                                        ->rows(2)
                                        ->placeholder(__('admin.business_profile.fields.about_meta_description_vi_placeholder')),

                                    Forms\Components\Textarea::make('extra.about_meta_description_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.about_meta_description_en').'</span>'))
                                        ->rows(2)
                                        ->placeholder(__('admin.business_profile.fields.about_meta_description_en_placeholder')),
                                ])
                                ->columns(2),

                            Section::make(__('admin.business_profile.sections.page_fallback_blog_index'))
                                ->description(__('admin.business_profile.sections.page_fallback_blog_index_desc'))
                                ->schema([
                                    Forms\Components\TextInput::make('extra.blog_index_title')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.blog_index_title_vi').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.blog_index_title_vi_placeholder')),

                                    Forms\Components\TextInput::make('extra.blog_index_title_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.blog_index_title_en').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.blog_index_title_en_placeholder')),

                                    Forms\Components\Textarea::make('extra.blog_index_description')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.blog_index_meta_description_vi').'</span>'))
                                        ->rows(2)
                                        ->placeholder(__('admin.business_profile.fields.blog_index_meta_description_vi_placeholder')),

                                    Forms\Components\Textarea::make('extra.blog_index_description_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.blog_index_meta_description_en').'</span>'))
                                        ->rows(2)
                                        ->placeholder(__('admin.business_profile.fields.blog_index_meta_description_en_placeholder')),
                                ])
                                ->columns(2),

                            Section::make(__('admin.business_profile.sections.page_fallback_search'))
                                ->description(__('admin.business_profile.sections.page_fallback_search_desc'))
                                ->schema([
                                    Forms\Components\TextInput::make('extra.search_title')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">🇻🇳 '.__('admin.business_profile.fields.search_title_vi').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.search_title_vi_placeholder')),

                                    Forms\Components\TextInput::make('extra.search_title_en')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">🇬🇧 '.__('admin.business_profile.fields.search_title_en').'</span>'))
                                        ->placeholder(__('admin.business_profile.fields.search_title_en_placeholder')),
                                ])
                                ->columns(2),
                        ]),

                    // ── JSON-LD ───────────────────────────────────────────────
                    Tab::make(__('admin.business_profile.tabs.jsonld'))
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Section::make(__('admin.business_profile.sections.live_schemas'))
                                ->description(__('admin.business_profile.sections.live_schemas_desc'))
                                ->schema([
                                    Placeholder::make('jsonld_preview')
                                        ->label('')
                                        ->content(function (): HtmlString {
                                            $service = app(BusinessJsonldService::class);
                                            $html = '';
                                            foreach (['vi' => '🇻🇳 VI', 'en' => '🇬🇧 EN'] as $locale => $label) {
                                                $html .= "<h3 style='font-size:0.9rem;font-weight:700;color:#1e293b;margin:24px 0 8px;border-bottom:2px solid #e2e8f0;padding-bottom:6px;'>{$label}</h3>";
                                                foreach ($service->getSchemas($locale) as $schema) {
                                                    $type = htmlspecialchars($schema['@type'] ?? 'Unknown');
                                                    $json = htmlspecialchars(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                                    $html .= "<p style='font-weight:600;font-size:0.85rem;color:#1e293b;margin:12px 0 4px;'>{$type}</p>";
                                                    $html .= "<pre style='white-space:pre-wrap;font-size:0.72rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;'>{$json}</pre>";
                                                }
                                            }

                                            return new HtmlString($html ?: '<em>No schemas generated.</em>');
                                        })
                                        ->columnSpanFull(),
                                ]),

                            Actions::make([
                                Action::make('flush_jsonld_cache')
                                    ->label(__('admin.business_profile.actions.flush_jsonld_cache'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('warning')
                                    ->requiresConfirmation()
                                    ->modalHeading(__('admin.business_profile.actions.flush_jsonld_cache_modal_heading'))
                                    ->modalDescription(__('admin.business_profile.actions.flush_jsonld_cache_modal_description'))
                                    ->action(function (): void {
                                        app(BusinessJsonldService::class)->flushCache();
                                        Notification::make()->title(__('admin.business_profile.notifications.jsonld_cache_flushed'))->success()->send();
                                    }),
                            ]),
                        ]),

                    // ── LLMs ──────────────────────────────────────────────────
                    Tab::make(__('admin.business_profile.tabs.llms'))
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make(__('admin.business_profile.sections.llms_documents'))
                                ->description(__('admin.business_profile.sections.llms_documents_desc'))
                                ->schema([
                                    Placeholder::make('llms_vi')
                                        ->label(__('admin.business_profile.fields.llms_vi_label'))
                                        ->content(function (): HtmlString {
                                            $doc = LlmsDocument::where('slug', 'business-vi')->first();
                                            if (! $doc) {
                                                return new HtmlString('<em class="text-gray-400">Document not found.</em>');
                                            }
                                            $file = 'llms/'.$doc->slug.'.txt';
                                            $content = Storage::disk('public')->exists($file)
                                                ? htmlspecialchars(Storage::disk('public')->get($file))
                                                : '';
                                            $updated = $doc->last_generated_at
                                                ? Carbon::parse($doc->last_generated_at)->format('d/m/Y H:i')
                                                : '—';

                                            return new HtmlString(
                                                "<div style='font-size:0.75rem;color:#64748b;margin-bottom:6px;'>Last generated: {$updated}</div>"
                                                ."<pre style='white-space:pre-wrap;font-size:0.72rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;max-height:320px;'>"
                                                .($content ?: '<em style="color:#94a3b8;">(empty — nhấn Regenerate để tạo)</em>')
                                                .'</pre>'
                                            );
                                        })
                                        ->columnSpanFull(),

                                    Placeholder::make('llms_en')
                                        ->label(__('admin.business_profile.fields.llms_en_label'))
                                        ->content(function (): HtmlString {
                                            $doc = LlmsDocument::where('slug', 'business-en')->first();
                                            if (! $doc) {
                                                return new HtmlString('<em class="text-gray-400">Document not found.</em>');
                                            }
                                            $file = 'llms/'.$doc->slug.'.txt';
                                            $content = Storage::disk('public')->exists($file)
                                                ? htmlspecialchars(Storage::disk('public')->get($file))
                                                : '';
                                            $updated = $doc->last_generated_at
                                                ? Carbon::parse($doc->last_generated_at)->format('d/m/Y H:i')
                                                : '—';

                                            return new HtmlString(
                                                "<div style='font-size:0.75rem;color:#64748b;margin-bottom:6px;'>Last generated: {$updated}</div>"
                                                ."<pre style='white-space:pre-wrap;font-size:0.72rem;line-height:1.6;background:#0f172a;border-radius:6px;padding:14px;color:#e2e8f0;overflow-x:auto;max-height:320px;'>"
                                                .($content ?: '<em style="color:#94a3b8;">(empty — nhấn Regenerate để tạo)</em>')
                                                .'</pre>'
                                            );
                                        })
                                        ->columnSpanFull(),
                                ]),

                            Actions::make([
                                Action::make('regenerate_llms')
                                    ->label(__('admin.business_profile.actions.regenerate_llms'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('gray')
                                    ->requiresConfirmation()
                                    ->modalHeading(__('admin.business_profile.actions.regenerate_llms_modal_heading'))
                                    ->modalDescription(__('admin.business_profile.actions.regenerate_llms_modal_description'))
                                    ->action(function (): void {
                                        $service = app(LlmsGeneratorService::class);
                                        $docs = LlmsDocument::where('is_active', true)
                                            ->where(fn ($q) => $q->where('slug', 'business')
                                                ->orWhere('slug', 'like', 'business-%'))
                                            ->get();
                                        foreach ($docs as $doc) {
                                            $service->generateDocument($doc);
                                        }
                                        Notification::make()->title(__('admin.business_profile.notifications.llms_regenerated'))->success()->send();
                                        redirect(BusinessProfileResource::getUrl());
                                    }),
                            ]),
                        ]),

                ])
                ->columnSpanFull(),
        ]);
    }

    // ── Table (not used — singleton) ──────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\EditBusinessProfile::route('/'),
        ];
    }
}
