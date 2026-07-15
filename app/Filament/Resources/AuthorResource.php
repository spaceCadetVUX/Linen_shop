<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthorResource\Pages;
use App\Models\Author;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.blog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.author');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            // ── Identity ──────────────────────────────────────────────────────
            Section::make(__('admin.author.sections.identity'))
                ->description(__('admin.author.sections.identity_desc'))
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\FileUpload::make('avatar')
                        ->label(__('admin.author.fields.profile_photo'))
                        ->disk('public')
                        ->directory('authors')
                        ->image()
                        ->imageEditor()
                        ->imagePreviewHeight('100')
                        ->nullable()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->live(debounce: 500)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))
                        )
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(table: Author::class, column: 'slug', ignoreRecord: true)
                        ->rules(['regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'])
                        ->helperText(__('admin.author.fields.slug_help'))
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('title')
                        ->label(__('admin.author.fields.job_title'))
                        ->placeholder(__('admin.author.fields.job_title_placeholder'))
                        ->helperText(__('admin.author.fields.job_title_help'))
                        ->columnSpan(2),
                ])
                ->columns(3),

            // ── Bio ───────────────────────────────────────────────────────────
            Section::make(__('admin.author.sections.bio'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Textarea::make('bio')
                        ->label(__('admin.author.fields.short_bio'))
                        ->rows(4)
                        ->helperText(__('admin.author.fields.short_bio_help'))
                        ->columnSpanFull(),

                    Forms\Components\TagsInput::make('expertise')
                        ->label(__('admin.author.fields.expertise'))
                        ->placeholder(__('admin.author.fields.expertise_placeholder'))
                        ->helperText(__('admin.author.fields.expertise_help'))
                        ->columnSpanFull(),
                ]),

            // ── Social presence ───────────────────────────────────────────────
            Section::make(__('admin.author.sections.social'))
                ->description(__('admin.author.sections.social_desc'))
                ->icon('heroicon-o-globe-alt')
                ->schema([
                    Forms\Components\TextInput::make('website')
                        ->label(__('admin.author.fields.website'))
                        ->url()
                        ->placeholder(__('admin.author.fields.website_placeholder'))
                        ->prefixIcon('heroicon-o-globe-alt'),

                    Forms\Components\TextInput::make('linkedin')
                        ->label(__('admin.author.fields.linkedin'))
                        ->url()
                        ->placeholder(__('admin.author.fields.linkedin_placeholder'))
                        ->prefixIcon('heroicon-o-link'),

                    Forms\Components\TextInput::make('twitter')
                        ->label(__('admin.author.fields.twitter'))
                        ->url()
                        ->placeholder(__('admin.author.fields.twitter_placeholder'))
                        ->prefixIcon('heroicon-o-at-symbol'),

                    Forms\Components\TextInput::make('facebook')
                        ->label(__('admin.author.fields.facebook'))
                        ->url()
                        ->placeholder(__('admin.author.fields.facebook_placeholder'))
                        ->prefixIcon('heroicon-o-link'),
                ])
                ->columns(2),

            // ── Account link ──────────────────────────────────────────────────
            Section::make(__('admin.author.sections.admin_account'))
                ->description(__('admin.author.sections.admin_account_desc'))
                ->icon('heroicon-o-key')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label(__('admin.author.fields.linked_account'))
                        ->options(User::query()->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false)
                        ->placeholder(__('admin.author.fields.linked_account_placeholder')),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('admin.author.fields.active'))
                        ->default(true)
                        ->helperText(__('admin.author.fields.active_help')),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin.author.fields.job_title_column'))
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('blog_posts_count')
                    ->label(__('admin.author.fields.posts'))
                    ->counts('blogPosts')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.author.fields.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.author.fields.updated'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('admin.author.fields.active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'edit' => Pages\EditAuthor::route('/{record}/edit'),
        ];
    }
}
