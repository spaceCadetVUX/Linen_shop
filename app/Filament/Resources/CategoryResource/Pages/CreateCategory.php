<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * Dehydrated `translations` data, captured here (runs through
     * $form->getState() and therefore holds RichEditor content as HTML, not
     * the raw Tiptap JSON array Livewire keeps in $this->data). afterCreate()
     * must read from here, never from $this->data directly — the raw array
     * blows up "Array to string conversion" on the rich_content column.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $translationsForSave = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationsForSave = $data['translations'] ?? [];

        // The internal slug field is ->hidden() in the form, so Livewire never
        // dehydrates it — categories.slug is NOT NULL, derive it here
        // (same approach as CreateProduct).
        $vi = $data['translations']['vi'] ?? [];
        $base = filled($vi['slug'] ?? null) ? $vi['slug'] : Str::slug($vi['name'] ?? ($data['name'] ?? ''));

        $data['slug'] = $this->uniqueSlug($base !== '' ? $base : 'category');

        return $data;
    }

    /** categories.slug has a plain unique index (soft-deleted rows included). */
    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;

        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    protected function afterCreate(): void
    {
        // SEO meta and GEO profile are saved automatically by Filament's
        // saveRelationships() via the Group::relationship() components in the form.

        // ── Translations ──────────────────────────────────────────────────────
        $translationsData = $this->translationsForSave;

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
