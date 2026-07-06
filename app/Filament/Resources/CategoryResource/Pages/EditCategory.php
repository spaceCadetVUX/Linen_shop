<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        // ── Translations ──────────────────────────────────────────────────────
        foreach (config('app.supported_locales') as $locale) {
            $translation = $record->translations()->where('locale', $locale)->first();

            if ($translation) {
                $data['translations'][$locale] = $translation->only([
                    'name', 'slug', 'description', 'rich_content',
                    'meta_title', 'meta_description',
                    'og_title', 'og_description',
                    'twitter_title', 'twitter_description',
                ]);
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Internal slug field is ->hidden() — keep categories.slug in sync with
        // the vi translation slug on save (same behaviour as EditProduct).
        $vi = $data['translations']['vi'] ?? [];
        $base = filled($vi['slug'] ?? null) ? $vi['slug'] : Str::slug($vi['name'] ?? ($data['name'] ?? ''));

        if ($base !== '') {
            $data['slug'] = $this->uniqueSlug($base);
        }

        return $data;
    }

    /** categories.slug has a plain unique index (soft-deleted rows included). */
    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;

        while (Category::withTrashed()
            ->where('slug', $slug)
            ->whereKeyNot($this->getRecord()->getKey())
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    protected function afterSave(): void
    {
        $state = $this->data;

        // SEO meta and GEO profile are saved automatically by Filament's
        // saveRelationships() via the Group::relationship() components in the form.

        // ── Translations ──────────────────────────────────────────────────────
        $translationsData = $state['translations'] ?? [];

        foreach (config('app.supported_locales') as $locale) {
            $localeData = $translationsData[$locale] ?? [];

            if (empty($localeData['name'])) {
                continue;
            }

            $this->record->translations()->updateOrCreate(
                ['locale' => $locale],
                collect($localeData)
                    ->only([
                        'name', 'slug', 'description', 'rich_content',
                        'meta_title', 'meta_description',
                        'og_title', 'og_description',
                        'twitter_title', 'twitter_description',
                    ])
                    ->filter(fn ($v) => $v !== null)
                    ->toArray()
            );
        }
    }
}
