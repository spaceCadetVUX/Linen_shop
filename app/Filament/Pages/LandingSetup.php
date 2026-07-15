<?php

namespace App\Filament\Pages;

use App\Enums\HomeEditorialScope;
use App\Models\BusinessProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LandingSetup extends Page
{
    // Ẩn khỏi sidebar — truy cập qua card trong PagesSetting hub.
    protected static bool $shouldRegisterNavigation = false;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Landing Page';

    protected string $view = 'filament.pages.landing-setup';

    public ?array $data = [];

    public function mount(): void
    {
        $landing = (array) (BusinessProfile::instance()->extra['landing'] ?? []);

        $this->form->fill([
            'hero_eyebrow' => $landing['hero_eyebrow'] ?? 'Mới ra mắt',
            'hero_eyebrow_en' => $landing['hero_eyebrow_en'] ?? 'New Arrivals',
            'hero_headline' => $landing['hero_headline'] ?? null,
            'hero_headline_en' => $landing['hero_headline_en'] ?? null,
            'hero_cta_label' => $landing['hero_cta_label'] ?? 'Khám phá lookbook',
            'hero_cta_label_en' => $landing['hero_cta_label_en'] ?? 'Explore lookbook',
            'hero_cta_url' => $landing['hero_cta_url'] ?? '/collections/lookbook',
            'hero_cta2_label' => $landing['hero_cta2_label'] ?? 'Khám phá thêm',
            'hero_cta2_label_en' => $landing['hero_cta2_label_en'] ?? 'Discover more',
            'hero_cta2_url' => $landing['hero_cta2_url'] ?? '/collections/new',
            'hero_image' => $landing['hero_image'] ?? null,

            'featured_enabled' => (bool) ($landing['featured_enabled'] ?? true),
            'featured_title' => $landing['featured_title'] ?? 'Sản phẩm nổi bật',
            'featured_title_en' => $landing['featured_title_en'] ?? 'Featured products',

            'newsletter_enabled' => (bool) ($landing['newsletter_enabled'] ?? true),
            'newsletter_heading' => $landing['newsletter_heading'] ?? 'Nhận ưu đãi mỗi tuần',
            'newsletter_body' => $landing['newsletter_body'] ?? null,

            'editorial_scope' => $landing['editorial_scope'] ?? HomeEditorialScope::Parents->value,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make(__('admin.landing_setup.sections.hero_banner'))
                    ->icon('heroicon-o-star')
                    ->description(__('admin.landing_setup.sections.hero_banner_desc'))
                    ->schema([
                        TextInput::make('hero_eyebrow')
                            ->label(__('admin.landing_setup.fields.eyebrow_vi'))
                            ->placeholder(__('admin.landing_setup.fields.eyebrow_vi_placeholder'))
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_eyebrow_en')
                            ->label(__('admin.landing_setup.fields.eyebrow_en'))
                            ->placeholder(__('admin.landing_setup.fields.eyebrow_en_placeholder'))
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_headline')
                            ->label(__('admin.landing_setup.fields.headline_vi'))
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_headline_en')
                            ->label(__('admin.landing_setup.fields.headline_en'))
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Fieldset::make(__('admin.landing_setup.fieldsets.cta1'))
                            ->schema([
                                TextInput::make('hero_cta_label')
                                    ->label(__('admin.landing_setup.fields.cta_text_vi'))
                                    ->placeholder(__('admin.landing_setup.fields.cta1_text_vi_placeholder'))
                                    ->maxLength(50)
                                    ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                                    ->columnSpan(1),
                                TextInput::make('hero_cta_label_en')
                                    ->label(__('admin.landing_setup.fields.cta_text_en'))
                                    ->placeholder(__('admin.landing_setup.fields.cta1_text_en_placeholder'))
                                    ->maxLength(50)
                                    ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                                    ->columnSpan(1),
                                TextInput::make('hero_cta_url')
                                    ->label(__('admin.landing_setup.fields.cta_url'))
                                    ->placeholder(__('admin.landing_setup.fields.cta1_url_placeholder'))
                                    ->maxLength(200)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        Fieldset::make(__('admin.landing_setup.fieldsets.cta2'))
                            ->schema([
                                TextInput::make('hero_cta2_label')
                                    ->label(__('admin.landing_setup.fields.cta_text_vi'))
                                    ->placeholder(__('admin.landing_setup.fields.cta2_text_vi_placeholder'))
                                    ->maxLength(50)
                                    ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                                    ->columnSpan(1),
                                TextInput::make('hero_cta2_label_en')
                                    ->label(__('admin.landing_setup.fields.cta_text_en'))
                                    ->placeholder(__('admin.landing_setup.fields.cta2_text_en_placeholder'))
                                    ->maxLength(50)
                                    ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                                    ->columnSpan(1),
                                TextInput::make('hero_cta2_url')
                                    ->label(__('admin.landing_setup.fields.cta_url'))
                                    ->placeholder(__('admin.landing_setup.fields.cta2_url_placeholder'))
                                    ->maxLength(200)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        FileUpload::make('hero_image')
                            ->label(__('admin.landing_setup.fields.hero_image'))
                            ->image()
                            ->disk('public')
                            ->directory('landing/hero')
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText(__('admin.landing_setup.fields.hero_image_help'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('admin.landing_setup.sections.featured'))
                    ->icon('heroicon-o-sparkles')
                    ->description(__('admin.landing_setup.sections.featured_desc'))
                    ->schema([
                        Toggle::make('featured_enabled')
                            ->label(__('admin.landing_setup.fields.show_section'))
                            ->columnSpanFull(),

                        TextInput::make('featured_title')
                            ->label(__('admin.landing_setup.fields.featured_title_vi'))
                            ->placeholder(__('admin.landing_setup.fields.featured_title_vi_placeholder'))
                            ->maxLength(80)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('featured_title_en')
                            ->label(__('admin.landing_setup.fields.featured_title_en'))
                            ->placeholder(__('admin.landing_setup.fields.featured_title_en_placeholder'))
                            ->maxLength(80)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make(__('admin.landing_setup.sections.editorial_grid'))
                    ->icon('heroicon-o-squares-2x2')
                    ->description(__('admin.landing_setup.sections.editorial_grid_desc'))
                    ->schema([
                        Select::make('editorial_scope')
                            ->label(__('admin.landing_setup.fields.editorial_scope'))
                            ->options(HomeEditorialScope::options())
                            ->default(HomeEditorialScope::Parents->value)
                            ->native(false)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.landing_setup.sections.newsletter'))
                    ->icon('heroicon-o-envelope')
                    ->description(__('admin.landing_setup.sections.newsletter_desc'))
                    ->schema([
                        Toggle::make('newsletter_enabled')
                            ->label(__('admin.landing_setup.fields.show_section'))
                            ->columnSpanFull(),

                        TextInput::make('newsletter_heading')
                            ->label(__('admin.landing_setup.fields.newsletter_heading'))
                            ->placeholder(__('admin.landing_setup.fields.newsletter_heading_placeholder'))
                            ->maxLength(80)
                            ->columnSpan(1),

                        TextInput::make('newsletter_body')
                            ->label(__('admin.landing_setup.fields.newsletter_body'))
                            ->maxLength(160)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.landing_setup.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $profile = BusinessProfile::instance();
        $extra = (array) ($profile->extra ?? []);

        $extra['landing'] = [
            'hero_eyebrow' => filled($data['hero_eyebrow']) ? trim($data['hero_eyebrow']) : null,
            'hero_eyebrow_en' => filled($data['hero_eyebrow_en']) ? trim($data['hero_eyebrow_en']) : null,
            'hero_headline' => filled($data['hero_headline']) ? trim($data['hero_headline']) : null,
            'hero_headline_en' => filled($data['hero_headline_en']) ? trim($data['hero_headline_en']) : null,
            'hero_cta_label' => filled($data['hero_cta_label']) ? trim($data['hero_cta_label']) : null,
            'hero_cta_label_en' => filled($data['hero_cta_label_en']) ? trim($data['hero_cta_label_en']) : null,
            'hero_cta_url' => filled($data['hero_cta_url']) ? trim($data['hero_cta_url']) : null,
            'hero_cta2_label' => filled($data['hero_cta2_label']) ? trim($data['hero_cta2_label']) : null,
            'hero_cta2_label_en' => filled($data['hero_cta2_label_en']) ? trim($data['hero_cta2_label_en']) : null,
            'hero_cta2_url' => filled($data['hero_cta2_url']) ? trim($data['hero_cta2_url']) : null,
            'hero_image' => $data['hero_image'] ?? null,
            'featured_enabled' => (bool) ($data['featured_enabled'] ?? true),
            'featured_title' => filled($data['featured_title']) ? trim($data['featured_title']) : null,
            'featured_title_en' => filled($data['featured_title_en']) ? trim($data['featured_title_en']) : null,
            'newsletter_enabled' => (bool) ($data['newsletter_enabled'] ?? true),
            'newsletter_heading' => filled($data['newsletter_heading']) ? trim($data['newsletter_heading']) : null,
            'newsletter_body' => filled($data['newsletter_body']) ? trim($data['newsletter_body']) : null,

            'editorial_scope' => HomeEditorialScope::tryFrom((string) ($data['editorial_scope'] ?? ''))?->value
                ?? HomeEditorialScope::Parents->value,
        ];

        $profile->extra = $extra;
        $profile->saveQuietly();

        Notification::make()
            ->title(__('admin.landing_setup.notifications.saved'))
            ->success()
            ->send();
    }
}
