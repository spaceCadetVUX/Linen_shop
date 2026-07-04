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
                Section::make('Blog Page Hero')
                    ->icon('heroicon-o-newspaper')
                    ->description('Ảnh hero đầu trang Journal (/bai-viet, /blog). Bỏ trống thì trang chỉ có chữ như hiện tại.')
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label('Ảnh Hero')
                            ->image()
                            ->disk('public')
                            ->directory('blog/hero')
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->helperText('Full-width dưới tiêu đề Journal. Khuyến nghị 1920×600px, ≤5MB, WebP.')
                            ->columnSpanFull(),

                        TextInput::make('hero_alt')
                            ->label('Alt ảnh Tiếng Việt')
                            ->placeholder('Mô tả nội dung ảnh — cho Google Images và người dùng screen reader')
                            ->maxLength(160)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('hero_alt_en')
                            ->label('Alt ảnh English')
                            ->placeholder('Describe what the image shows')
                            ->maxLength(160)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('SEO / Tab Title')
                    ->icon('heroicon-o-magnifying-glass')
                    ->description('Thẻ <title> (tiêu đề tab cạnh favicon) và meta description của trang danh sách bài viết. Hậu tố tên shop tự thêm, đừng gõ vào.')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Tab Title Tiếng Việt')
                            ->placeholder('Blog — Tin tức & Bài viết')
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('meta_title_en')
                            ->label('Tab Title English')
                            ->placeholder('Blog — News & Articles')
                            ->maxLength(120)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Textarea::make('meta_description')
                            ->label('Meta Description Tiếng Việt')
                            ->placeholder('Cập nhật kiến thức, xu hướng và câu chuyện từ chúng tôi.')
                            ->rows(2)
                            ->maxLength(300)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Textarea::make('meta_description_en')
                            ->label('Meta Description English')
                            ->placeholder('Insights, trends and stories from our team.')
                            ->rows(2)
                            ->maxLength(300)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
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
            ->title('Đã lưu cài đặt blog page')
            ->success()
            ->send();
    }
}
