<?php

namespace App\Filament\Pages;

use App\Models\BusinessProfile;
use App\Models\Product;
use App\Services\Product\ProductService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
            'new_products_label' => $megaMenu['new_products_label'] ?? 'Sản phẩm mới',
            'new_products_label_en' => $megaMenu['new_products_label_en'] ?? 'New Arrivals',
            'new_products_ids' => $megaMenu['new_products_ids'] ?? [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('admin.mega_menu_settings.sections.mega_menu'))
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->description(__('admin.mega_menu_settings.sections.mega_menu_desc'))
                    ->schema([
                        Toggle::make('enabled')
                            ->label(__('admin.mega_menu_settings.fields.enabled'))
                            ->helperText(__('admin.mega_menu_settings.fields.enabled_help'))
                            ->columnSpanFull(),

                        TextInput::make('collection_label')
                            ->label(__('admin.mega_menu_settings.fields.collection_label_vi'))
                            ->placeholder(__('admin.mega_menu_settings.fields.collection_label_vi_placeholder'))
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('collection_label_en')
                            ->label(__('admin.mega_menu_settings.fields.collection_label_en'))
                            ->placeholder(__('admin.mega_menu_settings.fields.collection_label_en_placeholder'))
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make(__('admin.mega_menu_settings.sections.new_products_column'))
                    ->icon('heroicon-o-sparkles')
                    ->description(__('admin.mega_menu_settings.sections.new_products_column_desc'))
                    ->schema([
                        TextInput::make('new_products_label')
                            ->label(__('admin.mega_menu_settings.fields.new_products_label_vi'))
                            ->placeholder(__('admin.mega_menu_settings.fields.new_products_label_vi_placeholder'))
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#f0fdf4;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        TextInput::make('new_products_label_en')
                            ->label(__('admin.mega_menu_settings.fields.new_products_label_en'))
                            ->placeholder(__('admin.mega_menu_settings.fields.new_products_label_en_placeholder'))
                            ->maxLength(60)
                            ->extraFieldWrapperAttributes(['style' => 'background:#eff6ff;padding:10px 12px;border-radius:8px;'])
                            ->columnSpan(1),

                        Select::make('new_products_ids')
                            ->label(__('admin.mega_menu_settings.fields.new_products_ids'))
                            ->helperText(__('admin.mega_menu_settings.fields.new_products_ids_help'))
                            ->multiple()
                            ->searchable()
                            ->reorderable()
                            ->preload(false)
                            ->getSearchResultsUsing(fn (string $search): array => Product::query()
                                ->active()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(20)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Product::query()
                                ->whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->all())
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.mega_menu_settings.actions.save'))
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
            'new_products_label' => filled($data['new_products_label']) ? trim($data['new_products_label']) : null,
            'new_products_label_en' => filled($data['new_products_label_en']) ? trim($data['new_products_label_en']) : null,
            'new_products_ids' => array_values((array) ($data['new_products_ids'] ?? [])),
        ];

        $profile->extra = $extra;
        $profile->saveQuietly();

        app(ProductService::class)->bustLatestMegaCache();

        Notification::make()
            ->title(__('admin.mega_menu_settings.notifications.saved'))
            ->success()
            ->send();
    }
}
