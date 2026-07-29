<?php

namespace App\Filament\Resources\BlogCategoryResource\Pages;

use App\Filament\Resources\BlogCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlogCategory extends EditRecord
{
    protected static string $resource = BlogCategoryResource::class;

    /**
     * Dehydrated `translations` data, captured from mutateFormDataBeforeSave
     * (runs through $form->getState() and therefore holds RichEditor content
     * as HTML, not the raw Tiptap JSON array Livewire keeps in $this->data).
     * saveTranslations() must read from here, never from $this->data directly
     * — the raw array blows up "Array to string conversion" on rich_content.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $translationsForSave = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->translationsForSave = $data['translations'] ?? [];

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        foreach (config('app.supported_locales') as $locale) {
            $translation = $record->translations()->where('locale', $locale)->first();

            if ($translation) {
                $data['translations'][$locale] = $translation->only([
                    'name', 'slug', 'description', 'rich_content', 'is_mcp_protected',
                ]);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->saveTranslations();
    }

    private function saveTranslations(): void
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
