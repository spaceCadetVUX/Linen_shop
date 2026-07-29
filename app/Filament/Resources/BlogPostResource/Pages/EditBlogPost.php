<?php

namespace App\Filament\Resources\BlogPostResource\Pages;

use App\Filament\Resources\BlogPostResource;
use App\Models\Seo\GeoEntityProfile;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    /**
     * Dehydrated `translations` data, captured from mutateFormDataBeforeSave
     * (runs through $form->getState() and therefore holds RichEditor content
     * as HTML, not the raw Tiptap JSON array Livewire keeps in $this->data).
     * saveTranslations() must read from here, never from $this->data directly
     * — the raw array blows up "Array to string conversion" on the body column.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $translationsForSave = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->translationsForSave = $data['translations'] ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('geoProfiles');

        $data['faq_items_vi'] = $this->record->geoProfile('vi')?->faq ?? [];
        $data['faq_items_en'] = $this->record->geoProfile('en')?->faq ?? [];

        foreach (config('app.supported_locales') as $locale) {
            $translation = $this->record->translations()->where('locale', $locale)->first();

            if ($translation) {
                $data['translations'][$locale] = $translation->only([
                    'title', 'slug', 'excerpt', 'body', 'is_mcp_protected',
                ]);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $state = $this->data;
        $morphClass = $this->record->getMorphClass();
        $modelId = $this->record->getKey();

        // ── FAQ → geo_entity_profiles.faq (per locale) ───────────────────────
        $normalizeFaq = fn (array $items): array => collect($items)
            ->filter(fn (array $item): bool => filled($item['question'] ?? null))
            ->map(fn (array $item): array => [
                'question' => trim($item['question']),
                'answer' => trim($item['answer'] ?? ''),
            ])
            ->values()
            ->toArray();

        foreach (['vi' => 'faq_items_vi', 'en' => 'faq_items_en'] as $locale => $field) {
            GeoEntityProfile::updateOrCreate(
                ['model_type' => $morphClass, 'model_id' => $modelId, 'locale' => $locale],
                ['faq' => $normalizeFaq($state[$field] ?? [])]
            );
        }

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

            // Only stamp protection onto a locale that actually has (or is
            // gaining, this save) real content — otherwise this creates AND
            // locks an empty seo_meta row auto-filled with placeholder values
            // for a locale nobody has written anything for yet, permanently
            // blocking MCP from ever filling it in with real content.
            $hasContent = filled($localeData['title'] ?? null)
                || $record->translations()->where('locale', $locale)->exists();

            if ($hasContent) {
                $record->seoMetas()->updateOrCreate(
                    ['locale' => $locale],
                    ['is_mcp_protected' => $mcpProtected]
                );
            }

            if (empty($localeData['title'])) {
                continue;
            }

            $record->translations()->updateOrCreate(
                ['locale' => $locale],
                collect($localeData)
                    ->only(['title', 'slug', 'excerpt', 'body', 'is_mcp_protected'])
                    ->filter(fn ($v) => $v !== null && $v !== '')
                    ->toArray()
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
