<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Forms\Plugins\MediaRichEditorPlugin;
use App\Models\Page;
use App\Models\PageTranslation;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('page_key')
                ->label('Page Key')
                ->required()
                ->unique(table: Page::class, column: 'page_key', ignoreRecord: true)
                ->helperText('Internal identifier — e.g. privacy-policy, terms, faq. Not shown publicly.')
                ->columnSpan(1),

            Forms\Components\Toggle::make('is_active')
                ->default(true)
                ->columnSpan(1),

            Tabs::make('Tabs')
                ->tabs([

                    Tab::make('🇻🇳 Tiếng Việt')
                        ->schema([
                            Group::make()
                                ->relationship('translationVi')
                                ->mutateRelationshipDataBeforeCreateUsing(
                                    fn (array $data) => ['locale' => 'vi', ...$data]
                                )
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Tiêu đề (vi)</span>'))
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state ?? '')))
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('slug')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Slug (vi)</span>'))
                                        ->required()
                                        ->unique(
                                            table: PageTranslation::class,
                                            column: 'slug',
                                            ignoreRecord: true,
                                            modifyRuleUsing: fn ($rule) => $rule->where('locale', 'vi'),
                                        )
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('body')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Nội dung (vi)</span>'))
                                        ->plugins([MediaRichEditorPlugin::make()])
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('meta_title')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Meta Title (vi)</span>'))
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('meta_description')
                                        ->label(new HtmlString('<span style="color:#16a34a;font-weight:600;">Meta Description (vi)</span>'))
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('🇬🇧 English')
                        ->schema([
                            Group::make()
                                ->relationship('translationEn')
                                ->mutateRelationshipDataBeforeCreateUsing(
                                    fn (array $data) => ['locale' => 'en', ...$data]
                                )
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Title (en)</span>'))
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state ?? '')))
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('slug')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Slug (en)</span>'))
                                        ->required()
                                        ->unique(
                                            table: PageTranslation::class,
                                            column: 'slug',
                                            ignoreRecord: true,
                                            modifyRuleUsing: fn ($rule) => $rule->where('locale', 'en'),
                                        )
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('body')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Body (en)</span>'))
                                        ->plugins([MediaRichEditorPlugin::make()])
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('meta_title')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Meta Title (en)</span>'))
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('meta_description')
                                        ->label(new HtmlString('<span style="color:#2563eb;font-weight:600;">Meta Description (en)</span>'))
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),
                ])
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('page_key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('translationVi.title')
                    ->label('Title (vi)')
                    ->color('success')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('translationEn.title')
                    ->label('Title (en)')
                    ->color('info')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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
            'index'  => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit'   => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
