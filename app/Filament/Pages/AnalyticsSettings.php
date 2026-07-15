<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\BusinessProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class AnalyticsSettings extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Setting';

    protected static ?string $navigationLabel = 'Analytics & Search Console';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.analytics-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    // ── Form state ────────────────────────────────────────────────────────────

    public ?array $data = [];

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $extra = (array) (BusinessProfile::instance()->extra ?? []);

        $this->form->fill([
            'ga4_id' => $extra['ga4_id'] ?? null,
            'gtm_id' => $extra['gtm_id'] ?? null,
            'gsc_meta' => $extra['gsc_meta'] ?? null,
            'ga4_active' => (bool) ($extra['ga4_active'] ?? true),
            'default_og_image' => $extra['og_image'] ?? null,
        ]);
    }

    // ── Form schema ───────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make(__('admin.analytics_settings.sections.og_image'))
                    ->icon('heroicon-o-photo')
                    ->description(__('admin.analytics_settings.sections.og_image_desc'))
                    ->schema([
                        FileUpload::make('default_og_image')
                            ->label(__('admin.analytics_settings.fields.og_image'))
                            ->image()
                            ->disk('public')
                            ->directory('og')
                            ->imagePreviewHeight('180')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->helperText(__('admin.analytics_settings.fields.og_image_help'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.analytics_settings.sections.ga4'))
                    ->icon('heroicon-o-chart-bar')
                    ->description(__('admin.analytics_settings.sections.ga4_desc'))
                    ->schema([
                        TextInput::make('ga4_id')
                            ->label(__('admin.analytics_settings.fields.ga4_id'))
                            ->placeholder(__('admin.analytics_settings.fields.ga4_id_placeholder'))
                            ->helperText(__('admin.analytics_settings.fields.ga4_id_help'))
                            ->columnSpan(1),

                        TextInput::make('gtm_id')
                            ->label(__('admin.analytics_settings.fields.gtm_id'))
                            ->placeholder(__('admin.analytics_settings.fields.gtm_id_placeholder'))
                            ->helperText(__('admin.analytics_settings.fields.gtm_id_help'))
                            ->columnSpan(1),

                        Toggle::make('ga4_active')
                            ->label(__('admin.analytics_settings.fields.ga4_active'))
                            ->helperText(__('admin.analytics_settings.fields.ga4_active_help'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('admin.analytics_settings.sections.gsc'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->description(__('admin.analytics_settings.sections.gsc_desc'))
                    ->schema([
                        TextInput::make('gsc_meta')
                            ->label(__('admin.analytics_settings.fields.gsc_meta'))
                            ->placeholder(__('admin.analytics_settings.fields.gsc_meta_placeholder'))
                            ->helperText(__('admin.analytics_settings.fields.gsc_meta_help'))
                            ->live(debounce: 400)
                            ->columnSpanFull(),

                        Placeholder::make('gsc_preview')
                            ->label(__('admin.analytics_settings.fields.gsc_preview_label'))
                            ->content(function (): HtmlString {
                                $val = $this->data['gsc_meta'] ?? null;

                                return new HtmlString(
                                    filled($val)
                                        ? '<code style="font-size:0.8rem;background:#f1f5f9;padding:6px 10px;border-radius:4px;display:block;">'
                                          .e('<meta name="google-site-verification" content="'.$val.'">')
                                          .'</code>'
                                        : '<em style="color:#94a3b8;">Chưa có verification code.</em>'
                                );
                            })
                            ->columnSpanFull(),
                    ]),

            ])
            ->statePath('data');
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.analytics_settings.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $profile = BusinessProfile::instance();
        $extra = (array) ($profile->extra ?? []);

        $extra['ga4_id'] = filled($data['ga4_id']) ? trim($data['ga4_id']) : null;
        $extra['gtm_id'] = filled($data['gtm_id']) ? trim($data['gtm_id']) : null;
        $extra['gsc_meta'] = filled($data['gsc_meta']) ? trim($data['gsc_meta']) : null;
        $extra['ga4_active'] = (bool) ($data['ga4_active'] ?? true);
        $extra['og_image'] = $data['default_og_image'] ?? null;

        $profile->extra = $extra;
        $profile->saveQuietly();

        Notification::make()
            ->title(__('admin.analytics_settings.notifications.saved'))
            ->success()
            ->send();
    }
}
