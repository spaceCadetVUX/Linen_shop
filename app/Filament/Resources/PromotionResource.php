<?php

namespace App\Filament\Resources;

use App\Enums\PromotionBannerPosition;
use App\Filament\Resources\PromotionResource\Pages;
use App\Models\Product;
use App\Models\Promotion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';

    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Khuyến mãi';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return (string) Promotion::active()->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.promotion.sections.banner'))
                ->schema([
                    Forms\Components\FileUpload::make('banner_image')
                        ->label(__('admin.promotion.fields.banner_image'))
                        ->disk('public')
                        ->visibility('public')
                        ->directory(fn () => 'promotions/'.now()->format('Y/m'))
                        ->image()
                        ->imagePreviewHeight('200')
                        ->getUploadedFileNameForStorageUsing(function ($file): string {
                            $dir = 'promotions/'.now()->format('Y/m');
                            $name = Str::slug(
                                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                            );
                            $ext = strtolower($file->getClientOriginalExtension());

                            if (empty($name)) {
                                $name = 'banner-'.now()->format('YmdHis');
                            }

                            $filename = "{$name}.{$ext}";
                            $counter = 1;

                            while (Storage::disk('public')->exists("{$dir}/{$filename}")) {
                                $filename = "{$name}-{$counter}.{$ext}";
                                $counter++;
                            }

                            return $filename;
                        })
                        ->columnSpanFull(),

                    Forms\Components\Select::make('banner_position')
                        ->label(__('admin.promotion.fields.banner_position'))
                        ->options(PromotionBannerPosition::options())
                        ->default(PromotionBannerPosition::Left->value)
                        ->native(false)
                        ->required()
                        ->helperText(__('admin.promotion.fields.banner_position_help'))
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('title')
                        ->label(__('admin.promotion.fields.title_vi'))
                        ->required()
                        ->maxLength(150)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('title_en')
                        ->label(__('admin.promotion.fields.title_en'))
                        ->maxLength(150)
                        ->columnSpan(1),
                ])
                ->columns(2),

            Section::make(__('admin.promotion.sections.cta'))
                ->schema([
                    Forms\Components\TextInput::make('cta_label')
                        ->label(__('admin.promotion.fields.cta_label_vi'))
                        ->maxLength(60)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('cta_label_en')
                        ->label(__('admin.promotion.fields.cta_label_en'))
                        ->maxLength(60)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('cta_url')
                        ->label(__('admin.promotion.fields.cta_url'))
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('admin.promotion.sections.schedule'))
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('admin.promotion.fields.is_active'))
                        ->default(true)
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label(__('admin.promotion.fields.starts_at'))
                        ->native(false)
                        ->helperText(__('admin.promotion.fields.starts_at_help'))
                        ->columnSpan(1),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label(__('admin.promotion.fields.ends_at'))
                        ->native(false)
                        ->helperText(__('admin.promotion.fields.ends_at_help'))
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('admin.promotion.fields.sort_order_form'))
                        ->numeric()
                        ->default(0)
                        ->helperText(__('admin.promotion.fields.sort_order_help'))
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('admin.promotion.sections.products'))
                ->schema([
                    Forms\Components\Select::make('product_ids')
                        ->label(__('admin.promotion.fields.product_ids'))
                        ->helperText(__('admin.promotion.fields.product_ids_help'))
                        ->multiple()
                        ->searchable()
                        ->reorderable()
                        ->preload(false)
                        ->getSearchResultsUsing(fn (string $search): array => Product::query()
                            ->active()
                            ->where(fn ($query) => $query
                                ->whereLike('name', "%{$search}%")
                                ->orWhereLike('sku', "%{$search}%"))
                            ->orderBy('name')
                            ->limit(20)
                            ->pluck('name', 'id')
                            ->all())
                        ->getOptionLabelsUsing(fn (array $values): array => Product::query()
                            ->whereIn('id', $values)
                            ->pluck('name', 'id')
                            ->all())
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('banner_image')
                    ->label(__('admin.promotion.fields.banner_column'))
                    ->disk('public')
                    ->height(50),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin.promotion.fields.title_column'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('banner_position')
                    ->label(__('admin.promotion.fields.position_column'))
                    ->formatStateUsing(fn (PromotionBannerPosition $state): string => $state->label()),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.promotion.fields.status_column'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'running' => 'Đang chạy',
                        'upcoming' => 'Sắp chạy',
                        'ended' => 'Đã kết thúc',
                        default => 'Tắt',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'running' => 'success',
                        'upcoming' => 'info',
                        'ended' => 'gray',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('admin.promotion.fields.starts_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder(__('admin.promotion.fields.dash_placeholder'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('admin.promotion.fields.ends_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder(__('admin.promotion.fields.dash_placeholder'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_ids')
                    ->label(__('admin.promotion.fields.product_count_column'))
                    ->state(fn (Promotion $record): int => count($record->product_ids ?? []))
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.promotion.fields.enabled_column'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.promotion.fields.sort_order_column'))
                    ->sortable()
                    ->alignCenter()
                    ->width('80px'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.promotion.fields.is_active')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}
