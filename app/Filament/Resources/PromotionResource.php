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
            Section::make('Banner')
                ->schema([
                    Forms\Components\FileUpload::make('banner_image')
                        ->label('Ảnh banner')
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
                        ->label('Vị trí banner')
                        ->options(PromotionBannerPosition::options())
                        ->default(PromotionBannerPosition::Left->value)
                        ->native(false)
                        ->required()
                        ->helperText('Banner nằm trái hoặc phải, sản phẩm luôn ở phía đối diện.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('title')
                        ->label('Tiêu đề (vi)')
                        ->required()
                        ->maxLength(150)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('title_en')
                        ->label('Tiêu đề (en)')
                        ->maxLength(150)
                        ->columnSpan(1),
                ])
                ->columns(2),

            Section::make('CTA (tuỳ chọn)')
                ->schema([
                    Forms\Components\TextInput::make('cta_label')
                        ->label('Text nút (vi)')
                        ->maxLength(60)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('cta_label_en')
                        ->label('Text nút (en)')
                        ->maxLength(60)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('cta_url')
                        ->label('URL đích')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Lên lịch')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Bật campaign')
                        ->default(true)
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Bắt đầu')
                        ->native(false)
                        ->helperText('Để trống = hiển thị ngay khi bật.')
                        ->columnSpan(1),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Kết thúc')
                        ->native(false)
                        ->helperText('Để trống = không đếm ngược, không tự tắt.')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Thứ tự slide')
                        ->numeric()
                        ->default(0)
                        ->helperText('Số nhỏ hơn hiện trước khi có nhiều campaign cùng active.')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Sản phẩm')
                ->schema([
                    Forms\Components\Select::make('product_ids')
                        ->label('Sản phẩm hiển thị trong campaign')
                        ->helperText('Tìm và chọn sản phẩm — kéo để đổi thứ tự. Giá bán lấy trực tiếp từ sale_price của sản phẩm.')
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
                    ->label('Banner')
                    ->disk('public')
                    ->height(50),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('banner_position')
                    ->label('Vị trí')
                    ->formatStateUsing(fn (PromotionBannerPosition $state): string => $state->label()),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
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
                    ->label('Bắt đầu')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Kết thúc')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_ids')
                    ->label('SP')
                    ->state(fn (Promotion $record): int => count($record->product_ids ?? []))
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Bật')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->width('80px'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Bật campaign'),
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
