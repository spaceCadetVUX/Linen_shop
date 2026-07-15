<?php

namespace App\Filament\Resources;

use App\Enums\JsonldSchemaType;
use App\Filament\Resources\JsonldTemplateResource\Pages;
use App\Models\Seo\JsonldTemplate;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JsonldTemplateResource extends Resource
{
    protected static ?string $model = JsonldTemplate::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?int $navigationSort = 40;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.seo_geo');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.jsonld_template');
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Forms\Components\Select::make('schema_type')
                ->label(__('admin.jsonld_template.fields.schema_type'))
                ->options(collect(JsonldSchemaType::cases())->mapWithKeys(
                    fn (JsonldSchemaType $case) => [$case->value => $case->value]
                ))
                ->disabled(),

            Forms\Components\TextInput::make('label')
                ->label(__('admin.jsonld_template.fields.label'))
                ->disabled(),

            Forms\Components\Toggle::make('is_auto_generated')
                ->label(__('admin.jsonld_template.fields.auto_generated'))
                ->helperText(__('admin.jsonld_template.fields.auto_generated_help'))
                ->disabled()
                ->columnSpanFull(),

            Forms\Components\Textarea::make('template')
                ->label(__('admin.jsonld_template.fields.template'))
                ->rows(20)
                ->formatStateUsing(fn ($state) => is_array($state)
                    ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $state
                )
                ->disabled()
                ->columnSpanFull(),

            Forms\Components\KeyValue::make('placeholders')
                ->label(__('admin.jsonld_template.fields.placeholders'))
                ->keyLabel(__('admin.jsonld_template.fields.placeholders_key_label'))
                ->valueLabel(__('admin.jsonld_template.fields.placeholders_value_label'))
                ->disabled()
                ->helperText(__('admin.jsonld_template.fields.placeholders_help'))
                ->columnSpanFull(),

        ])->columns(2);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('schema_type')
                    ->label(__('admin.jsonld_template.fields.schema_type'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('label')
                    ->searchable(),

                IconColumn::make('is_auto_generated')
                    ->label(__('admin.jsonld_template.fields.auto_generated'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJsonldTemplates::route('/'),
            'view' => Pages\ViewJsonldTemplate::route('/{record}'),
        ];
    }
}
