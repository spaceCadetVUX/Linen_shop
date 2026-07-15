<?php

namespace App\Filament\Resources;

use App\Enums\RedirectType;
use App\Filament\Resources\RedirectResource\Pages;
use App\Models\Seo\Redirect;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.seo_geo');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.redirect');
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Forms\Components\TextInput::make('from_path')
                ->label(__('admin.redirect.fields.from_path'))
                ->required()
                ->placeholder(__('admin.redirect.fields.from_path_placeholder'))
                ->unique(table: Redirect::class, column: 'from_path', ignoreRecord: true)
                ->rules(['regex:/^\//'])
                ->validationMessages(['regex' => __('admin.redirect.validation.from_path_regex')])
                ->helperText(__('admin.redirect.fields.from_path_help')),

            Forms\Components\TextInput::make('to_path')
                ->label(__('admin.redirect.fields.to_path'))
                ->required()
                ->placeholder(__('admin.redirect.fields.to_path_placeholder'))
                ->helperText(__('admin.redirect.fields.to_path_help')),

            Forms\Components\Select::make('type')
                ->label(__('admin.redirect.fields.type'))
                ->options([
                    RedirectType::Permanent->value => '301 — Permanent',
                    RedirectType::Temporary->value => '302 — Temporary',
                ])
                ->default(RedirectType::Permanent->value)
                ->required(),

            Forms\Components\Select::make('locale')
                ->label(__('admin.redirect.fields.locale'))
                ->options([
                    'vi' => '🇻🇳 Tiếng Việt (vi)',
                    'en' => '🇬🇧 English (en)',
                ])
                ->placeholder(__('admin.redirect.fields.locale_placeholder'))
                ->nullable()
                ->helperText(__('admin.redirect.fields.locale_help')),

            Forms\Components\Toggle::make('is_active')
                ->label(__('admin.redirect.fields.active'))
                ->default(true)
                ->columnSpanFull(),

        ])->columns(2);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('from_path')
                    ->label(__('admin.redirect.fields.from_column'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Path copied'),

                TextColumn::make('to_path')
                    ->label(__('admin.redirect.fields.to_column'))
                    ->searchable()
                    ->limit(60),

                TextColumn::make('locale')
                    ->label(__('admin.redirect.fields.locale_column'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'all')
                    ->color(fn (?string $state): string => match ($state) {
                        'vi' => 'success',
                        'en' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('type')
                    ->label(__('admin.redirect.fields.type_column'))
                    ->badge()
                    ->formatStateUsing(fn (RedirectType $state): string => $state->value.' '.($state === RedirectType::Permanent ? 'Permanent' : 'Temporary'))
                    ->color(fn (RedirectType $state): string => match ($state) {
                        RedirectType::Permanent => 'warning',
                        RedirectType::Temporary => 'info',
                    }),

                TextColumn::make('hits')
                    ->label(__('admin.redirect.fields.hits'))
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('admin.redirect.fields.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.redirect.fields.active')),

                Tables\Filters\SelectFilter::make('locale')
                    ->options([
                        'vi' => '🇻🇳 vi',
                        'en' => '🇬🇧 en',
                    ])
                    ->placeholder(__('admin.redirect.fields.locale_filter_placeholder')),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        RedirectType::Permanent->value => '301 Permanent',
                        RedirectType::Temporary->value => '302 Temporary',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('toggleActive')
                        ->label(__('admin.redirect.actions.toggle_active'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->update(['is_active' => ! $record->is_active]);
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
