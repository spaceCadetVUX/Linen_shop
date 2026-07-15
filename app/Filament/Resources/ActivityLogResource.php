<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\ActivityLogResource\Pages;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.activity_log');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    // Read-only resource — no form needed
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100])
            ->columns([
                TextColumn::make('log_name')
                    ->label(__('admin.activity_log.fields.log'))
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('admin.activity_log.fields.event'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label(__('admin.activity_log.fields.subject'))
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? class_basename($state)
                        : '—'
                    )
                    ->color('gray'),

                TextColumn::make('causer.name')
                    ->label(__('admin.activity_log.fields.by'))
                    ->placeholder(__('admin.activity_log.fields.by_placeholder'))
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('admin.activity_log.fields.when'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label(__('admin.activity_log.fields.log_channel'))
                    ->options(fn (): array => Activity::query()
                        ->distinct()
                        ->pluck('log_name', 'log_name')
                        ->filter()
                        ->toArray()
                    ),

                Filter::make('created_at')
                    ->label(__('admin.activity_log.fields.date_range'))
                    ->form([
                        DatePicker::make('from')
                            ->label(__('admin.activity_log.fields.from')),
                        DatePicker::make('until')
                            ->label(__('admin.activity_log.fields.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null,
                                fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when($data['until'] ?? null,
                                fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(__('admin.activity_log.actions.activity_detail_modal_heading'))
                    ->infolist(fn (Schema $schema): Schema => $schema->schema([
                        Section::make(__('admin.activity_log.sections.info'))->schema([
                            TextEntry::make('log_name')->label(__('admin.activity_log.fields.log'))->badge()->color('primary'),
                            TextEntry::make('description')->label(__('admin.activity_log.fields.event'))->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'created' => 'success',
                                    'updated' => 'warning',
                                    'deleted' => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('subject_type')->label(__('admin.activity_log.fields.subject'))
                                ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                            TextEntry::make('causer.name')->label(__('admin.activity_log.fields.by'))->placeholder(__('admin.activity_log.fields.by_placeholder')),
                            TextEntry::make('created_at')->label(__('admin.activity_log.fields.when'))->dateTime(),
                        ])->columns(3),

                        Section::make(__('admin.activity_log.sections.old_values'))
                            ->schema([
                                KeyValueEntry::make('attribute_changes.old')->label('')->placeholder(__('admin.activity_log.fields.dash_placeholder')),
                            ])
                            ->visible(fn (Activity $record): bool => filled($record->attribute_changes['old'] ?? null)),

                        Section::make(__('admin.activity_log.sections.new_values'))
                            ->schema([
                                KeyValueEntry::make('attribute_changes.attributes')->label('')->placeholder(__('admin.activity_log.fields.dash_placeholder')),
                            ])
                            ->visible(fn (Activity $record): bool => filled($record->attribute_changes['attributes'] ?? null)),
                    ])),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label(__('admin.activity_log.actions.delete_selected')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
