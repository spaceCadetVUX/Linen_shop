<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use App\Models\BlogPostTranslation;
use App\Models\CategoryTranslation;
use App\Models\Media;
use App\Models\ProductTranslation;
use App\Services\Media\MediaUploadService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.media');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid(['sm' => 2, 'md' => 3, 'xl' => 4])
            ->defaultPaginationPageOption(30)
            ->paginationPageOptions([30, 60, 120])
            ->modifyQueryUsing(fn (Builder $query) => $query->latest())
            ->recordClasses('rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800')
            ->columns([
                Stack::make([
                    ImageColumn::make('thumb_path')
                        ->disk('public')
                        ->height(180)
                        ->extraAttributes(['style' => 'display:block;width:100%;overflow:hidden;line-height:0;'])
                        ->extraImgAttributes([
                            'loading' => 'lazy',
                            'style' => 'width:100%;height:180px;object-fit:cover;display:block;',
                        ])
                        ->defaultImageUrl(fn (Media $record): ?string => $record->isImage()
                            ? Storage::disk('public')->url($record->path)
                            : null
                        ),

                    Panel::make([
                        TextColumn::make('display_name')
                            ->state(fn (Media $record): string => $record->title ?: $record->original_name)
                            ->limit(24)
                            ->weight(FontWeight::Medium)
                            ->alignment(Alignment::Center)
                            ->extraAttributes(['class' => 'truncate w-full text-center block'])
                            ->searchable(query: function (Builder $query, string $search): Builder {
                                return $query->where(function (Builder $q) use ($search) {
                                    $q->where('title', 'like', "%{$search}%")
                                        ->orWhere('original_name', 'like', "%{$search}%");
                                });
                            }),

                        TextColumn::make('size')
                            ->formatStateUsing(fn (?int $state): string => $state
                                ? ($state >= 1_048_576
                                    ? number_format($state / 1_048_576, 1).' MB'
                                    : number_format($state / 1024, 1).' KB')
                                : '—'
                            )
                            ->alignment(Alignment::Center)
                            ->color('gray'),
                    ])->extraAttributes(['class' => 'px-3 py-2 space-y-0.5 text-center overflow-hidden']),
                ])->extraAttributes(['class' => 'h-full']),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.media.fields.file_type'))
                    ->options([
                        'image' => 'Hình ảnh',
                        'video' => 'Video',
                        'document' => 'Tài liệu',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'image' => $query->where('mime_type', 'like', 'image/%'),
                            'video' => $query->where('mime_type', 'like', 'video/%'),
                            'document' => $query->where('mime_type', 'not like', 'image/%')
                                ->where('mime_type', 'not like', 'video/%'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                // Copy URL — opens modal with readonly URL input for easy copy
                Action::make('copyLink')
                    ->label(__('admin.media.actions.copy_link'))
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->fillForm(fn (Media $record): array => ['url' => $record->url])
                    ->form([
                        TextInput::make('url')
                            ->label(__('admin.media.fields.url'))
                            ->readOnly()
                            ->extraInputAttributes([
                                'x-ref' => 'urlInput',
                                'x-on:click' => 'navigator.clipboard.writeText($el.value)',
                            ])
                            ->helperText(__('admin.media.fields.url_help')),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('admin.media.actions.close')),

                // Delete — check usage before removing from disk
                Action::make('delete')
                    ->label(__('admin.media.actions.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.media.actions.delete_modal_heading'))
                    ->modalDescription(fn (Media $record): string => __('admin.media.actions.delete_modal_description', ['name' => $record->original_name])
                    )
                    ->action(function (Media $record): void {
                        if (static::isInUse($record)) {
                            Notification::make()
                                ->danger()
                                ->title(__('admin.media.notifications.cannot_delete_title'))
                                ->body(__('admin.media.notifications.cannot_delete_body'))
                                ->persistent()
                                ->send();

                            return;
                        }

                        Storage::disk($record->disk)->delete($record->path);

                        if ($record->thumb_path) {
                            Storage::disk($record->disk)->delete($record->thumb_path);
                        }

                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title(__('admin.media.notifications.deleted'))
                            ->send();
                    }),
            ])
            ->headerActions([
                // Upload new files through MediaUploadService (hash dedup)
                Action::make('upload')
                    ->label(__('admin.media.actions.upload'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('files')
                            ->label(__('admin.media.fields.choose_file'))
                            ->multiple()
                            ->storeFiles(false)
                            ->acceptedFileTypes(['image/*', 'video/*', 'application/pdf'])
                            ->maxSize(20480)
                            ->helperText(__('admin.media.fields.choose_file_help'))
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $service = app(MediaUploadService::class);
                        $count = 0;

                        foreach (Arr::wrap($data['files']) as $file) {
                            if ($file instanceof TemporaryUploadedFile) {
                                $service->upload($file);
                                $count++;
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title(__('admin.media.notifications.upload_success', ['count' => $count]))
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('deleteSelected')
                    ->label(__('admin.media.actions.delete_selected'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $blocked = 0;
                        $deleted = 0;

                        foreach ($records as $record) {
                            if (static::isInUse($record)) {
                                $blocked++;

                                continue;
                            }

                            Storage::disk($record->disk)->delete($record->path);
                            if ($record->thumb_path) {
                                Storage::disk($record->disk)->delete($record->thumb_path);
                            }
                            $record->delete();
                            $deleted++;
                        }

                        if ($deleted > 0) {
                            Notification::make()->success()->title(__('admin.media.notifications.deleted_count', ['count' => $deleted]))->send();
                        }
                        if ($blocked > 0) {
                            Notification::make()->warning()->title(__('admin.media.notifications.blocked_count', ['count' => $blocked]))->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
        ];
    }

    /**
     * Check if a media file's path is referenced in any rich content field.
     * Add new models/columns here as rich_content is expanded to other models.
     */
    private static function isInUse(Media $record): bool
    {
        $path = $record->path;

        return BlogPostTranslation::where('body', 'like', "%{$path}%")->exists()
            || CategoryTranslation::where('rich_content', 'like', "%{$path}%")->exists()
            || ProductTranslation::where('description', 'like', "%{$path}%")->exists();
    }
}
