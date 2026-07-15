<?php

namespace App\Filament\Pages;

use App\Models\BusinessProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShopSetting extends Page
{
    // Ẩn khỏi sidebar — truy cập qua card trong PagesSetting hub.
    protected static bool $shouldRegisterNavigation = false;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $title = 'Shop Page Setting';

    protected string $view = 'filament.pages.shop-setting';

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.shop_setting');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $extra = (array) (BusinessProfile::instance()->extra ?? []);
        $shop = (array) ($extra['shop'] ?? []);

        $this->form->fill([
            'h1' => $shop['h1'] ?? null,
            'h1_en' => $shop['h1_en'] ?? null,
            'intro' => $shop['intro'] ?? null,
            'intro_en' => $shop['intro_en'] ?? null,
            'hero_image' => $shop['hero_image'] ?? null,
            'hero_alt' => $shop['hero_alt'] ?? null,
            'hero_alt_en' => $shop['hero_alt_en'] ?? null,

            // Key extra.product_catalog_* cũ (trước ở BusinessProfileResource
            // tab Page Fallbacks) — giữ nguyên chỗ lưu, chỉ đổi editor.
            'meta_title' => $extra['product_catalog_title'] ?? null,
            'meta_title_en' => $extra['product_catalog_title_en'] ?? null,
            'meta_description' => $extra['product_catalog_description'] ?? null,
            'meta_description_en' => $extra['product_catalog_description_en'] ?? null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('admin.shop_setting.sections.hero'))
                    ->icon('heroicon-o-shopping-bag')
                    ->description(__('admin.shop_setting.sections.hero_desc'))
                    ->schema([
                        TextInput::make('h1')
                            ->label(__('admin.shop_setting.fields.h1_vi'))
                            ->placeholder(__('admin.shop_setting.fields.h1_vi_placeholder'))
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('h1_en')
                            ->label(__('admin.shop_setting.fields.h1_en'))
                            ->placeholder(__('admin.shop_setting.fields.h1_en_placeholder'))
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Textarea::make('intro')
                            ->label(__('admin.shop_setting.fields.intro_vi'))
                            ->placeholder(__('admin.shop_setting.fields.intro_vi_placeholder'))
                            ->rows(3)
                            ->maxLength(500)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Textarea::make('intro_en')
                            ->label(__('admin.shop_setting.fields.intro_en'))
                            ->placeholder(__('admin.shop_setting.fields.intro_en_placeholder'))
                            ->rows(3)
                            ->maxLength(500)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        FileUpload::make('hero_image')
                            ->label(__('admin.shop_setting.fields.hero_image'))
                            ->image()
                            ->disk('public')
                            ->directory('shop/hero')
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText(__('admin.shop_setting.fields.hero_image_help'))
                            ->columnSpanFull(),

                        TextInput::make('hero_alt')
                            ->label(__('admin.shop_setting.fields.hero_alt_vi'))
                            ->placeholder(__('admin.shop_setting.fields.hero_alt_vi_placeholder'))
                            ->maxLength(160)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_alt_en')
                            ->label(__('admin.shop_setting.fields.hero_alt_en'))
                            ->placeholder(__('admin.shop_setting.fields.hero_alt_en_placeholder'))
                            ->maxLength(160)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make(__('admin.shop_setting.sections.seo_tab_title'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->description(__('admin.shop_setting.sections.seo_tab_title_desc'))
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('admin.shop_setting.fields.meta_title_vi'))
                            ->placeholder(__('admin.shop_setting.fields.meta_title_vi_placeholder'))
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('meta_title_en')
                            ->label(__('admin.shop_setting.fields.meta_title_en'))
                            ->placeholder(__('admin.shop_setting.fields.meta_title_en_placeholder'))
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Textarea::make('meta_description')
                            ->label(__('admin.shop_setting.fields.meta_description_vi'))
                            ->placeholder(__('admin.shop_setting.fields.meta_description_vi_placeholder'))
                            ->rows(2)
                            ->maxLength(300)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Textarea::make('meta_description_en')
                            ->label(__('admin.shop_setting.fields.meta_description_en'))
                            ->placeholder(__('admin.shop_setting.fields.meta_description_en_placeholder'))
                            ->rows(2)
                            ->maxLength(300)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
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
                ->label(__('admin.shop_setting.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $profile = BusinessProfile::instance();
        $extra = (array) ($profile->extra ?? []);

        $extra['shop'] = [
            'h1' => filled($data['h1']) ? trim($data['h1']) : null,
            'h1_en' => filled($data['h1_en']) ? trim($data['h1_en']) : null,
            'intro' => filled($data['intro']) ? trim($data['intro']) : null,
            'intro_en' => filled($data['intro_en']) ? trim($data['intro_en']) : null,
            'hero_image' => $data['hero_image'] ?? null,
            'hero_alt' => filled($data['hero_alt']) ? trim($data['hero_alt']) : null,
            'hero_alt_en' => filled($data['hero_alt_en']) ? trim($data['hero_alt_en']) : null,
        ];

        // Key top-level cũ — ProductController đọc qua Setting::get().
        $extra['product_catalog_title'] = filled($data['meta_title']) ? trim($data['meta_title']) : null;
        $extra['product_catalog_title_en'] = filled($data['meta_title_en']) ? trim($data['meta_title_en']) : null;
        $extra['product_catalog_description'] = filled($data['meta_description']) ? trim($data['meta_description']) : null;
        $extra['product_catalog_description_en'] = filled($data['meta_description_en']) ? trim($data['meta_description_en']) : null;

        $profile->extra = $extra;
        $profile->saveQuietly();

        Notification::make()
            ->title(__('admin.shop_setting.notifications.saved'))
            ->success()
            ->send();
    }
}
