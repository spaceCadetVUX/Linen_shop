<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    /** Chỉ được xem/sửa/xóa account có level thấp hơn mình — chặn cả cùng cấp lẫn cao hơn qua direct URL. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $currentRole = auth()->user()?->role;

        if ($currentRole !== null) {
            $manageableRoles = static::manageableRoles($currentRole);

            if ($manageableRoles !== null) {
                $query->whereIn('role', $manageableRoles);
            }
        }

        return $query;
    }

    /** Roles the given role is allowed to manage (strictly lower level), or null for no restriction (Admin). */
    protected static function manageableRoles(UserRole $role): ?array
    {
        if ($role === UserRole::Admin) {
            return null;
        }

        return collect(UserRole::cases())
            ->filter(fn (UserRole $r) => $role->level() > $r->level())
            ->map(fn (UserRole $r) => $r->value)
            ->values()
            ->all();
    }

    public static function form(Schema $form): Schema
    {
        $isEdit = $form->getOperation() === 'edit';

        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Tên')
                ->required(),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('role')
                ->label('Role')
                ->options(function () {
                    $currentRole = auth()->user()?->role;
                    $roles = $currentRole === UserRole::Admin
                        ? UserRole::cases()
                        : static::manageableRoles($currentRole ?? UserRole::Customer);

                    return collect($roles)->mapWithKeys(fn ($r) => [
                        ($r instanceof UserRole ? $r->value : $r) => ucfirst($r instanceof UserRole ? $r->value : $r),
                    ]);
                })
                // Filament tự validate state submit lên against options() ở trên (in: rule server-side) —
                // không cần thêm rule thủ công.
                ->default(UserRole::Customer->value)
                ->native(false)
                ->required(),

            Forms\Components\TextInput::make('password')
                ->label($isEdit ? 'Mật khẩu mới (để trống = giữ nguyên)' : 'Mật khẩu')
                ->password()
                ->revealable()
                ->required(! $isEdit)
                ->minLength(8)
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => bcrypt($state)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        UserRole::Admin => 'warning',
                        UserRole::Manager => 'info',
                        UserRole::Customer => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof UserRole ? ucfirst($state->value) : $state),

                Tables\Columns\TextColumn::make('tokens_count')
                    ->label('Tokens')
                    ->getStateUsing(fn ($record) => $record->tokens()->count())
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime(timezone: 'Asia/Ho_Chi_Minh')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),

                DeleteAction::make()
                    ->label('Delete & Revoke tokens')
                    ->hidden(fn ($record) => $record->id === auth()->id())
                    ->before(function ($record) {
                        $record->tokens()->delete();
                    })
                    ->successNotificationTitle('User deleted — all tokens revoked'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete selected')
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->id === auth()->id()) {
                                    Notification::make()
                                        ->title('Không thể xóa tài khoản của chính mình')
                                        ->warning()
                                        ->send();

                                    return false;
                                }
                                $record->tokens()->delete();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
