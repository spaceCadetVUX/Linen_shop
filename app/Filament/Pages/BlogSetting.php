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

class BlogSetting extends Page
{
    // Ẩn khỏi sidebar — truy cập qua card trong PagesSetting hub.
    protected static bool $shouldRegisterNavigation = false;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Blog Page';

    protected static ?string $title = 'Blog Page Setting';

    protected string $view = 'filament.pages.blog-setting';

    public ?array $data = [];

    public function mount(): void
    {
        $extra = (array) (BusinessProfile::instance()->extra ?? []);
        $blog = (array) ($extra['blog'] ?? []);

        $this->form->fill([
            'hero_image' => $blog['hero_image'] ?? null,
            'hero_alt' => $blog['hero_alt'] ?? null,
            'hero_alt_en' => $blog['hero_alt_en'] ?? null,

            // Key extra.blog_index_* — BlogController::index đọc qua Setting::get().
            'meta_title' => $extra['blog_index_title'] ?? null,
            'meta_title_en' => $extra['blog_index_title_en'] ?? null,
            'meta_description' => $extra['blog_index_description'] ?? null,
            'meta_description_en' => $extra['blog_index_description_en'] ?? null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('admin.blog_setting.sections.hero'))
                    ->icon('heroicon-o-newspaper')
                    ->description(__('admin.blog_setting.sections.hero_desc'))
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label(__('admin.blog_setting.fields.hero_image'))
                            ->image()
                            ->disk('public')
                            ->directory('blog/hero')
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->helperText(__('admin.blog_setting.fields.hero_image_help'))
                            ->columnSpanFull(),

                        TextInput::make('hero_alt')
                            ->label(__('admin.blog_setting.fields.hero_alt_vi'))
                            ->placeholder(__('admin.blog_setting.fields.hero_alt_vi_placeholder'))
                            ->maxLength(160)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_alt_en')
                            ->label(__('admin.blog_setting.fields.hero_alt_en'))
                            ->placeholder(__('admin.blog_setting.fields.hero_alt_en_placeholder'))
                            ->maxLength(160)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make(__('admin.blog_setting.sections.seo_tab_title'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->description(__('admin.blog_setting.sections.seo_tab_title_desc'))
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('admin.blog_setting.fields.meta_title_vi'))
                            ->placeholder(__('admin.blog_setting.fields.meta_title_vi_placeholder'))
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('meta_title_en')
                            ->label(__('admin.blog_setting.fields.meta_title_en'))
                            ->placeholder(__('admin.blog_setting.fields.meta_title_en_placeholder'))
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Textarea::make('meta_description')
                            ->label(__('admin.blog_setting.fields.meta_description_vi'))
                            ->placeholder(__('admin.blog_setting.fields.meta_description_vi_placeholder'))
                            ->rows(2)
                            ->maxLength(300)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Textarea::make('meta_description_en')
                            ->label(__('admin.blog_setting.fields.meta_description_en'))
                            ->placeholder(__('admin.blog_setting.fields.meta_description_en_placeholder'))
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
                ->label(__('admin.blog_setting.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $profile = BusinessProfile::instance();
        $extra = (array) ($profile->extra ?? []);

        $extra['blog'] = [
            'hero_image' => $data['hero_image'] ?? null,
            'hero_alt' => filled($data['hero_alt']) ? trim($data['hero_alt']) : null,
            'hero_alt_en' => filled($data['hero_alt_en']) ? trim($data['hero_alt_en']) : null,
        ];

        // Key top-level — BlogController::index đọc qua Setting::get().
        $extra['blog_index_title'] = filled($data['meta_title']) ? trim($data['meta_title']) : null;
        $extra['blog_index_title_en'] = filled($data['meta_title_en']) ? trim($data['meta_title_en']) : null;
        $extra['blog_index_description'] = filled($data['meta_description']) ? trim($data['meta_description']) : null;
        $extra['blog_index_description_en'] = filled($data['meta_description_en']) ? trim($data['meta_description_en']) : null;

        $profile->extra = $extra;
        $profile->saveQuietly();

        Notification::make()
            ->title(__('admin.blog_setting.notifications.saved'))
            ->success()
            ->send();
    }
}
