<?php

namespace App\Services\Page;

use App\Models\Page;
use App\Repositories\Eloquent\PageRepository;

class PageService
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {}

    /**
     * Active static pages shaped as {name, url} for the footer "Thông tin"
     * column. Falls back to the app fallback locale's translation when a
     * page has no translation for the current locale (mirrors PageController).
     */
    public function getFooterPages(string $locale): array
    {
        return $this->pageRepository->getActiveList()
            ->map(function (Page $page) use ($locale) {
                $translation = $page->translation($locale);

                if (! $translation) {
                    return null;
                }

                return [
                    'name' => $translation->title,
                    'url' => route("{$translation->locale}.page.show", ['slug' => $translation->slug]),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Active static pages for the footer copyright bar (Privacy Policy,
     * Terms, Contact), matched by page_key rather than a hardcoded slug so
     * this stays correct regardless of what title/slug the admin gives each
     * translation. A key with no active page yet (or no translation for
     * this locale) is simply omitted — never a broken link, and never
     * duplicated against getFooterPages() above by callers that already
     * show every active page.
     */
    public function getLegalLinks(string $locale): array
    {
        $pagesByKey = $this->pageRepository->getActiveList()->keyBy('page_key');

        return collect(['privacy-policy', 'terms', 'contact'])
            ->map(function (string $key) use ($pagesByKey, $locale) {
                $translation = $pagesByKey->get($key)?->translation($locale);

                if (! $translation) {
                    return null;
                }

                return [
                    'name' => $translation->title,
                    'url' => route("{$translation->locale}.page.show", ['slug' => $translation->slug]),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
