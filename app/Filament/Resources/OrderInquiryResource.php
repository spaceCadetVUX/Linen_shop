<?php

namespace App\Filament\Resources;

use App\Enums\OrderInquiryStatus;
use App\Filament\Resources\OrderInquiryResource\Pages;
use App\Models\OrderInquiry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

    protected static ?int $navigationSort = 65;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.order_inquiry');
    }

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
            Forms\Components\TextInput::make('name')->label(__('admin.order_inquiry.fields.customer_name'))->disabled(),
            Forms\Components\TextInput::make('phone')->label(__('admin.order_inquiry.fields.phone'))->disabled(),
            Forms\Components\TextInput::make('email')->label(__('admin.order_inquiry.fields.email'))->disabled(),
            Forms\Components\TextInput::make('channel')->label(__('admin.order_inquiry.fields.channel'))->disabled()
                ->formatStateUsing(fn ($state) => $state?->value ?? $state),

            Forms\Components\Textarea::make('message')
                ->label(__('admin.order_inquiry.fields.message_form'))
                ->rows(8)
                ->disabled()
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->label(__('admin.order_inquiry.fields.status'))
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
                    ->label(__('admin.order_inquiry.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.order_inquiry.fields.customer_name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin.order_inquiry.fields.phone'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('channel')
                    ->label(__('admin.order_inquiry.fields.channel'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->value ?? $state),

                Tables\Columns\TextColumn::make('message')
                    ->label(__('admin.order_inquiry.fields.message_table'))
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.order_inquiry.fields.status'))
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
                    ->label(__('admin.order_inquiry.actions.mark_contacted'))
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (OrderInquiry $record): bool => $record->status === OrderInquiryStatus::New)
                    ->action(function (OrderInquiry $record): void {
                        $record->update(['status' => OrderInquiryStatus::Contacted]);
                        Notification::make()->title(__('admin.order_inquiry.notifications.marked_contacted'))->success()->send();
                    }),

                Action::make('mark_closed')
                    ->label(__('admin.order_inquiry.actions.mark_closed'))
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (OrderInquiry $record): bool => $record->status !== OrderInquiryStatus::Closed)
                    ->action(function (OrderInquiry $record): void {
                        $record->update(['status' => OrderInquiryStatus::Closed]);
                        Notification::make()->title(__('admin.order_inquiry.notifications.marked_closed'))->success()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
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
