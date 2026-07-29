<?php

namespace App\Filament\Resources\BlogCategoryResource\Pages;

use App\Filament\Resources\BlogCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogCategory extends CreateRecord
{
    protected static string $resource = BlogCategoryResource::class;

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

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $translationsData = $this->translationsForSave;

        // Single combined toggle in the form (translations.vi.is_mcp_protected) —
        // mirrored onto 'en' + both seo_meta rows explicitly, same as Product.
        $mcpProtected = (bool) ($translationsData['vi']['is_mcp_protected'] ?? false);

        foreach (config('app.supported_locales') as $locale) {
            $localeData = $translationsData[$locale] ?? [];
            $localeData['is_mcp_protected'] = $mcpProtected;

            $record->seoMetas()->updateOrCreate(
                ['locale' => $locale],
                ['is_mcp_protected' => $mcpProtected]
            );

            if (empty($localeData['name'])) {
                continue;
            }

            $record->translations()->updateOrCreate(
                ['locale' => $locale],
                collect($localeData)
                    ->only(['name', 'slug', 'description', 'rich_content', 'is_mcp_protected'])
                    ->filter(fn ($v) => $v !== null)
                    ->toArray()
            );
        }
    }
}
