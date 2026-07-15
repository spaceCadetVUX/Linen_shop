<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\PersonalAccessTokenResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.personal_access_token');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label(__('admin.personal_access_token.fields.user'))
                ->options(
                    User::orderBy('name')->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => "{$u->name} — joined {$u->created_at->format('d/m/Y')}",
                        ])
                )
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label(__('admin.personal_access_token.fields.token_name'))
                ->placeholder(__('admin.personal_access_token.fields.token_name_placeholder'))
                ->helperText(__('admin.personal_access_token.fields.token_name_help'))
                ->required(),

            Forms\Components\CheckboxList::make('abilities')
                ->label(__('admin.personal_access_token.fields.abilities'))
                ->options([
                    'mcp:read' => 'mcp:read — Đọc context (GET)',
                    'mcp:write' => 'mcp:write — Tạo/sửa draft (PUT)',
                    'mcp:publish' => 'mcp:publish — Publish/activate (PATCH)',
                ])
                ->columns(1)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.personal_access_token.fields.token_name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('tokenable.name')
                    ->label(__('admin.personal_access_token.fields.user')),

                Tables\Columns\TextColumn::make('abilities')
                    ->label(__('admin.personal_access_token.fields.abilities'))
                    ->badge()
                    ->separator(',')
                    ->getStateUsing(fn ($record) => implode(',', $record->abilities ?? [])),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label(__('admin.personal_access_token.fields.last_used'))
                    ->dateTime(timezone: 'Asia/Ho_Chi_Minh')
                    ->placeholder(__('admin.personal_access_token.fields.never_placeholder'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.personal_access_token.fields.created'))
                    ->dateTime(timezone: 'Asia/Ho_Chi_Minh')
                    ->sortable(),
            ])
            ->actions([
                DeleteAction::make()
                    ->label(__('admin.personal_access_token.actions.revoke'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.personal_access_token.actions.revoke_selected')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPersonalAccessTokens::route('/'),
            'create' => Pages\CreatePersonalAccessToken::route('/create'),
        ];
    }
}
