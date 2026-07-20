<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

/**
 * Hub gom các trang cấu hình page-level về một mục sidebar duy nhất.
 * Mỗi trang con ẩn khỏi sidebar ($shouldRegisterNavigation = false)
 * và xuất hiện ở đây dưới dạng card — thêm trang mới = thêm 1 entry
 * vào getCards().
 */
class PagesSetting extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $title = 'Pages Setting';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.pages-setting';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.setting');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.pages_setting');
    }

    /**
     * @return array<int, array{label: string, description: string, icon: string, url: string, newTab?: bool}>
     */
    public function getCards(): array
    {
        return [
            [
                'label' => 'Landing Page',
                'description' => 'Hero, sản phẩm nổi bật, promo banner và editorial grid của trang chủ.',
                'icon' => 'heroicon-o-home',
                'url' => LandingSetup::getUrl(),
            ],
            [
                'label' => 'Shop Setting',
                'description' => 'Tiêu đề H1, đoạn mô tả và ảnh hero cho trang danh sách sản phẩm.',
                'icon' => 'heroicon-o-shopping-bag',
                'url' => ShopSetting::getUrl(),
                'newTab' => true,
            ],
            [
                'label' => 'Blog Page Setting',
                'description' => 'Ảnh hero và SEO (tab title, meta description) cho trang danh sách bài viết.',
                'icon' => 'heroicon-o-newspaper',
                'url' => BlogSetting::getUrl(),
                'newTab' => true,
            ],
            // Thêm page setting mới = thêm 1 entry tại đây
            // ('newTab' => true nếu muốn mở tab mới).
        ];
    }
}
