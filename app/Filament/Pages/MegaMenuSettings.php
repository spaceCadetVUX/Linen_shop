<?php

namespace App\Filament\Pages;

use App\Models\BusinessProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MegaMenuSettings extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static \UnitEnum|string|null $navigationGroup = 'Setting';

    protected static ?string $navigationLabel = 'Mega Menu';

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.pages.mega-menu-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $megaMenu = (array) (BusinessProfile::instance()->extra['mega_menu'] ?? []);

        $this->form->fill([
            'enabled' => (bool) ($megaMenu['enabled'] ?? true),
            'collection_label' => $megaMenu['collection_label'] ?? 'Bộ sưu tập',
            'collection_label_en' => $megaMenu['collection_label_en'] ?? 'Collections',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Mega Menu')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->description('Cấu hình menu điều hướng chính (header). Cấu trúc dữ liệu (danh mục, cột, link) sẽ được bổ sung sau.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Bật Mega Menu')
                            ->helperText('Tắt để dùng menu mặc định thay vì mega menu.')
                            ->columnSpanFull(),

                        TextInput::make('collection_label')
                            ->label('Nhãn "Bộ sưu tập" Tiếng Việt')
                            ->placeholder('Bộ sưu tập')
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('collection_label_en')
                            ->label('Nhãn "Bộ sưu tập" English')
                            ->placeholder('Collections')
                            ->maxLength(60)
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

        $extra['mega_menu'] = [
            'enabled' => (bool) ($data['enabled'] ?? true),
            'collection_label' => filled($data['collection_label']) ? trim($data['collection_label']) : null,
            'collection_label_en' => filled($data['collection_label_en']) ? trim($data['collection_label_en']) : null,
        ];

        $profile->extra = $extra;
        $profile->saveQuietly();

        Notification::make()
            ->title('Đã lưu cài đặt Mega Menu')
            ->success()
            ->send();
    }
}
