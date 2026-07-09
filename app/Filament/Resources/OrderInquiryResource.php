<?php

namespace App\Filament\Resources;

use App\Enums\OrderInquiryStatus;
use App\Filament\Resources\OrderInquiryResource\Pages;
use App\Models\OrderInquiry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderInquiryResource extends Resource
{
    protected static ?string $model = OrderInquiry::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-phone-arrow-up-right';

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Order Inquiries';

    protected static ?int $navigationSort = 65;

    public static function getNavigationBadge(): ?string
    {
        return (string) OrderInquiry::where('status', OrderInquiryStatus::New)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return OrderInquiry::where('status', OrderInquiryStatus::New)->exists() ? 'warning' : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')->label('Tên khách')->disabled(),
            Forms\Components\TextInput::make('phone')->label('SĐT')->disabled(),
            Forms\Components\TextInput::make('email')->label('Email')->disabled(),
            Forms\Components\TextInput::make('channel')->label('Kênh')->disabled()
                ->formatStateUsing(fn ($state) => $state?->value ?? $state),

            Forms\Components\Textarea::make('message')
                ->label('Nội dung đơn hàng')
                ->rows(8)
                ->disabled()
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options([
                    'new' => 'Mới',
                    'contacted' => 'Đã liên hệ',
                    'closed' => 'Đã đóng',
                ])
                ->native(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Tên khách')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('SĐT')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('channel')
                    ->label('Kênh')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->value ?? $state),

                Tables\Columns\TextColumn::make('message')
                    ->label('Đơn hàng')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof OrderInquiryStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof OrderInquiryStatus ? $state->color() : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'Mới',
                        'contacted' => 'Đã liên hệ',
                        'closed' => 'Đã đóng',
                    ]),
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'zalo' => 'Zalo',
                        'phone' => 'Điện thoại',
                        'email' => 'Email',
                    ]),
            ])
            ->actions([
                Action::make('mark_contacted')
                    ->label('Đã liên hệ')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (OrderInquiry $record): bool => $record->status === OrderInquiryStatus::New)
                    ->action(function (OrderInquiry $record): void {
                        $record->update(['status' => OrderInquiryStatus::Contacted]);
                        Notification::make()->title('Đã đánh dấu liên hệ')->success()->send();
                    }),

                Action::make('mark_closed')
                    ->label('Đóng')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (OrderInquiry $record): bool => $record->status !== OrderInquiryStatus::Closed)
                    ->action(function (OrderInquiry $record): void {
                        $record->update(['status' => OrderInquiryStatus::Closed]);
                        Notification::make()->title('Đã đóng yêu cầu')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderInquiries::route('/'),
            'edit' => Pages\EditOrderInquiry::route('/{record}/edit'),
        ];
    }
}
