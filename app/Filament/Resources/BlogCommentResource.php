<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogCommentResource\Pages;
use App\Models\BlogComment;
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
use Filament\Tables\Table;

class BlogCommentResource extends Resource
{
    protected static ?string $model = BlogComment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.blog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.blog_comment');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_approved', false)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Textarea::make('body')
                ->label(__('admin.blog_comment.fields.comment'))
                ->disabled()
                ->rows(5)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_approved')
                ->label(__('admin.blog_comment.fields.approved'))
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('post.title')
                    ->label(__('admin.blog_comment.fields.post'))
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('admin.blog_comment.fields.user'))
                    ->placeholder(__('admin.blog_comment.fields.dash_placeholder')),

                Tables\Columns\TextColumn::make('body')
                    ->label(__('admin.blog_comment.fields.comment'))
                    ->limit(80),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label(__('admin.blog_comment.fields.approved'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label(__('admin.blog_comment.fields.approved')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label(__('admin.blog_comment.actions.approve_selected'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_approved' => true]))
                        ->requiresConfirmation(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogComments::route('/'),
            'edit' => Pages\EditBlogComment::route('/{record}/edit'),
        ];
    }
}
