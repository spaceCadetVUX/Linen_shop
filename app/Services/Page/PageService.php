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
}
