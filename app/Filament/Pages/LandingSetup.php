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

    protected static BackedEnum|string|null $navigationIcon  = 'heroicon-o-home';
    protected static ?string               $navigationLabel = 'Landing Page';

    protected string $view = 'filament.pages.landing-setup';

    public ?array $data = [];

    public function mount(): void
    {
        $landing = (array) (BusinessProfile::instance()->extra['landing'] ?? []);

        $this->form->fill([
            'hero_eyebrow'       => $landing['hero_eyebrow']       ?? 'Mới ra mắt',
            'hero_eyebrow_en'    => $landing['hero_eyebrow_en']    ?? 'New Arrivals',
            'hero_headline'      => $landing['hero_headline']      ?? null,
            'hero_headline_en'   => $landing['hero_headline_en']   ?? null,
            'hero_cta_label'     => $landing['hero_cta_label']     ?? 'Khám phá lookbook',
            'hero_cta_label_en'  => $landing['hero_cta_label_en']  ?? 'Explore lookbook',
            'hero_cta_url'       => $landing['hero_cta_url']       ?? '/collections/lookbook',
            'hero_cta2_label'    => $landing['hero_cta2_label']    ?? 'Khám phá thêm',
            'hero_cta2_label_en' => $landing['hero_cta2_label_en'] ?? 'Discover more',
            'hero_cta2_url'      => $landing['hero_cta2_url']      ?? '/collections/new',
            'hero_image'         => $landing['hero_image']         ?? null,

            'featured_enabled'   => (bool) ($landing['featured_enabled'] ?? true),
            'featured_title'     => $landing['featured_title']     ?? 'Sản phẩm nổi bật',

            'promo_enabled'      => (bool) ($landing['promo_enabled'] ?? false),
            'promo_image'        => $landing['promo_image']        ?? null,
            'promo_url'          => $landing['promo_url']          ?? null,
            'promo_alt'          => $landing['promo_alt']          ?? null,

            'newsletter_enabled' => (bool) ($landing['newsletter_enabled'] ?? true),
            'newsletter_heading' => $landing['newsletter_heading'] ?? 'Nhận ưu đãi mỗi tuần',
            'newsletter_body'    => $landing['newsletter_body']    ?? null,

            'editorial_scope'    => $landing['editorial_scope'] ?? HomeEditorialScope::Parents->value,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Hero Banner')
                    ->icon('heroicon-o-star')
                    ->description('Phần đầu trang — hiển thị ngay khi vào homepage.')
                    ->schema([
                        TextInput::make('hero_eyebrow')
                            ->label('Eyebrow Tiếng Việt')
                            ->placeholder('Mới ra mắt')
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_eyebrow_en')
                            ->label('Eyebrow English')
                            ->placeholder('New Arrivals')
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_headline')
                            ->label('Tiêu đề chính (h1) Tiếng Việt')
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_headline_en')
                            ->label('Tiêu đề chính (h1) English')
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Fieldset::make('Link trái')
                            ->schema([
                                TextInput::make('hero_cta_label')
                                    ->label('Text Tiếng Việt')
                                    ->placeholder('Khám phá lookbook')
                                    ->maxLength(50)
                                    ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                                    ->columnSpan(1),
                                TextInput::make('hero_cta_label_en')
                                    ->label('Text English')
                                    ->placeholder('Explore lookbook')
                                    ->maxLength(50)
                                    ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                                    ->columnSpan(1),
                                TextInput::make('hero_cta_url')
                                    ->label('URL đích')
                                    ->placeholder('/collections/lookbook')
                                    ->maxLength(200)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        Fieldset::make('Link phải')
                            ->schema([
                                TextInput::make('hero_cta2_label')
                                    ->label('Text Tiếng Việt')
                                    ->placeholder('Khám phá thêm')
                                    ->maxLength(50)
                                    ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                                    ->columnSpan(1),
                                TextInput::make('hero_cta2_label_en')
                                    ->label('Text English')
                                    ->placeholder('Discover more')
                                    ->maxLength(50)
                                    ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                                    ->columnSpan(1),
                                TextInput::make('hero_cta2_url')
                                    ->label('URL đích')
                                    ->placeholder('/collections/new')
                                    ->maxLength(200)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        FileUpload::make('hero_image')
                            ->label('Ảnh nền Hero')
                            ->image()
                            ->disk('public')
                            ->directory('landing/hero')
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->helperText('Khuyến nghị 1920×800px, ≤5MB, WebP.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Sản phẩm nổi bật')
                    ->icon('heroicon-o-sparkles')
                    ->description('Section giới thiệu sản phẩm nổi bật trên homepage.')
                    ->schema([
                        Toggle::make('featured_enabled')
                            ->label('Hiển thị section')
                            ->columnSpanFull(),

                        TextInput::make('featured_title')
                            ->label('Tiêu đề section')
                            ->placeholder('Sản phẩm nổi bật')
                            ->maxLength(80)
                            ->columnSpanFull(),
                    ]),

                Section::make('Editorial Grid')
                    ->icon('heroicon-o-squares-2x2')
                    ->description('3 ô ảnh danh mục nằm dưới hero banner — ảnh, tên, thứ tự lấy trực tiếp từ Category management.')
                    ->schema([
                        Select::make('editorial_scope')
                            ->label('Danh mục hiển thị')
                            ->options(HomeEditorialScope::options())
                            ->default(HomeEditorialScope::Parents->value)
                            ->native(false)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Banner khuyến mãi')
                    ->icon('heroicon-o-megaphone')
                    ->description('Banner quảng cáo / khuyến mãi theo mùa.')
                    ->schema([
                        Toggle::make('promo_enabled')
                            ->label('Hiển thị banner')
                            ->columnSpanFull(),

                        FileUpload::make('promo_image')
                            ->label('Ảnh banner')
                            ->image()
                            ->disk('public')
                            ->directory('landing/promo')
                            ->imagePreviewHeight('160')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                            ->maxSize(3072)
                            ->helperText('Định dạng JPG, PNG, WebP, GIF. Tối đa 3MB.')
                            ->columnSpanFull(),

                        TextInput::make('promo_url')
                            ->label('Link khi click')
                            ->placeholder('/sale')
                            ->maxLength(200)
                            ->columnSpan(1),

                        TextInput::make('promo_alt')
                            ->label('Alt text (SEO)')
                            ->maxLength(120)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Newsletter')
                    ->icon('heroicon-o-envelope')
                    ->description('Form đăng ký nhận email newsletter.')
                    ->schema([
                        Toggle::make('newsletter_enabled')
                            ->label('Hiển thị section')
                            ->columnSpanFull(),

                        TextInput::make('newsletter_heading')
                            ->label('Tiêu đề')
                            ->placeholder('Nhận ưu đãi mỗi tuần')
                            ->maxLength(80)
                            ->columnSpan(1),

                        TextInput::make('newsletter_body')
                            ->label('Mô tả ngắn')
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
                ->label('Lưu cài đặt')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $profile = BusinessProfile::instance();
        $extra   = (array) ($profile->extra ?? []);

        $extra['landing'] = [
            'hero_eyebrow'       => filled($data['hero_eyebrow'])       ? trim($data['hero_eyebrow'])       : null,
            'hero_eyebrow_en'    => filled($data['hero_eyebrow_en'])    ? trim($data['hero_eyebrow_en'])    : null,
            'hero_headline'      => filled($data['hero_headline'])      ? trim($data['hero_headline'])      : null,
            'hero_headline_en'   => filled($data['hero_headline_en'])   ? trim($data['hero_headline_en'])   : null,
            'hero_cta_label'     => filled($data['hero_cta_label'])     ? trim($data['hero_cta_label'])     : null,
            'hero_cta_label_en'  => filled($data['hero_cta_label_en'])  ? trim($data['hero_cta_label_en'])  : null,
            'hero_cta_url'       => filled($data['hero_cta_url'])       ? trim($data['hero_cta_url'])       : null,
            'hero_cta2_label'    => filled($data['hero_cta2_label'])    ? trim($data['hero_cta2_label'])    : null,
            'hero_cta2_label_en' => filled($data['hero_cta2_label_en']) ? trim($data['hero_cta2_label_en']) : null,
            'hero_cta2_url'      => filled($data['hero_cta2_url'])      ? trim($data['hero_cta2_url'])      : null,
            'hero_image'         => $data['hero_image']                 ?? null,
            'featured_enabled'   => (bool) ($data['featured_enabled']  ?? true),
            'featured_title'     => filled($data['featured_title'])     ? trim($data['featured_title'])     : null,
            'promo_enabled'      => (bool) ($data['promo_enabled']      ?? false),
            'promo_image'        => $data['promo_image']                ?? null,
            'promo_url'          => filled($data['promo_url'])          ? trim($data['promo_url'])          : null,
            'promo_alt'          => filled($data['promo_alt'])          ? trim($data['promo_alt'])          : null,
            'newsletter_enabled' => (bool) ($data['newsletter_enabled'] ?? true),
            'newsletter_heading' => filled($data['newsletter_heading']) ? trim($data['newsletter_heading']) : null,
            'newsletter_body'    => filled($data['newsletter_body'])    ? trim($data['newsletter_body'])    : null,

            'editorial_scope'    => HomeEditorialScope::tryFrom((string) ($data['editorial_scope'] ?? ''))?->value
                ?? HomeEditorialScope::Parents->value,
        ];

        $profile->extra = $extra;
        $profile->saveQuietly();

        Notification::make()
            ->title('Đã lưu cài đặt landing page')
            ->success()
            ->send();
    }
}
