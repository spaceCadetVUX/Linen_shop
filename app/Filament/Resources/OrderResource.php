<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static \UnitEnum|string|null $navigationGroup = 'Commerce';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return (string) Order::where('status', OrderStatus::Pending->value)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            // ── Customer Info ─────────────────────────────────────────────────
            Section::make(__('admin.order.sections.customer'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('user.name')
                            ->label(__('admin.order.fields.name'))
                            ->placeholder(__('admin.order.fields.dash_placeholder')),

                        TextEntry::make('user.email')
                            ->label(__('admin.order.fields.email'))
                            ->placeholder(__('admin.order.fields.dash_placeholder')),

                        TextEntry::make('user.phone')
                            ->label(__('admin.order.fields.phone'))
                            ->placeholder(__('admin.order.fields.dash_placeholder')),
                    ]),
                ]),

            // ── Shipping Address ──────────────────────────────────────────────
            Section::make(__('admin.order.sections.shipping_address'))
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('shipping_address.full_name')
                            ->label(__('admin.order.fields.full_name')),

                        TextEntry::make('shipping_address.phone')
                            ->label(__('admin.order.fields.phone')),

                        TextEntry::make('shipping_address.address_line')
                            ->label(__('admin.order.fields.address'))
                            ->columnSpan(2),

                        TextEntry::make('shipping_address.city')
                            ->label(__('admin.order.fields.city')),

                        TextEntry::make('shipping_address.province')
                            ->label(__('admin.order.fields.province')),
                    ]),
                ]),

            // ── Order Items ───────────────────────────────────────────────────
            Section::make(__('admin.order.sections.items'))
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            TextEntry::make('product_name')
                                ->label(__('admin.order.fields.product')),

                            TextEntry::make('product_sku')
                                ->label(__('admin.order.fields.sku')),

                            TextEntry::make('quantity')
                                ->label(__('admin.order.fields.qty')),

                            TextEntry::make('unit_price')
                                ->label(__('admin.order.fields.unit_price'))
                                ->money('VND'),

                            TextEntry::make('subtotal')
                                ->label(__('admin.order.fields.subtotal'))
                                ->money('VND'),
                        ])
                        ->columns(5),
                ]),

            // ── Order Totals ──────────────────────────────────────────────────
            Section::make(__('admin.order.sections.order_summary'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('total_amount')
                            ->label(__('admin.order.fields.total'))
                            ->money('VND'),

                        TextEntry::make('payment_status')
                            ->label(__('admin.order.fields.payment'))
                            ->badge()
                            ->color(fn (PaymentStatus $state) => match ($state) {
                                PaymentStatus::Unpaid => 'warning',
                                PaymentStatus::Paid => 'success',
                                PaymentStatus::Refunded => 'info',
                            }),

                        TextEntry::make('payment_method')
                            ->label(__('admin.order.fields.method'))
                            ->placeholder(__('admin.order.fields.dash_placeholder')),
                    ]),

                    TextEntry::make('note')
                        ->label(__('admin.order.fields.note'))
                        ->placeholder(__('admin.order.fields.dash_placeholder'))
                        ->columnSpanFull(),
                ]),

        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('items'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.order.fields.order_id'))
                    ->formatStateUsing(fn (string $state): string => strtoupper(substr($state, 0, 8)))
                    ->copyable()
                    ->copyMessage('UUID copied')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label(__('admin.order.fields.customer_column'))
                    ->searchable()
                    ->placeholder(__('admin.order.fields.dash_placeholder')),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state) => match ($state) {
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Processing => 'info',
                        OrderStatus::Shipped => 'primary',
                        OrderStatus::Delivered => 'success',
                        OrderStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('payment_status')
                    ->label(__('admin.order.fields.payment'))
                    ->badge()
                    ->color(fn (PaymentStatus $state) => match ($state) {
                        PaymentStatus::Unpaid => 'warning',
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Refunded => 'info',
                    }),

                TextColumn::make('total_amount')
                    ->label(__('admin.order.fields.total'))
                    ->money('VND')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label(__('admin.order.fields.items_count'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $case) => [$case->value => ucfirst($case->value)]
                    )),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('admin.order.fields.payment_status'))
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $case) => [$case->value => ucfirst($case->value)]
                    )),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label(__('admin.order.fields.from')),
                        DatePicker::make('until')->label(__('admin.order.fields.until')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                ViewAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
