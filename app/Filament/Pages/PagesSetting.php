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

    protected static \UnitEnum|string|null $navigationGroup = 'Setting';

    protected static ?string $navigationLabel = 'Pages Setting';

    protected static ?string $title = 'Pages Setting';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.pages-setting';

    /**
     * @return array<int, array{label: string, description: string, icon: string, url: string, newTab?: bool}>
     */
    public function getCards(): array
    {
        return [
            [
                'label' => 'Landing Page',
                'description' => 'Hero, sản phẩm nổi bật, promo banner, newsletter và editorial grid của trang chủ.',
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
            // Thêm page setting mới = thêm 1 entry tại đây
            // ('newTab' => true nếu muốn mở tab mới).
        ];
    }
}
