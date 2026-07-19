<?php

namespace App\Repositories\Eloquent;

use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;

class PageRepository extends BaseRepository
{
    protected function model(): string
    {
        return Page::class;
    }

    // ── List ──────────────────────────────────────────────────────────────────

    /**
     * All active static pages with translations, ordered by creation order.
     * Used to populate the footer "Thông tin" column.
     */
    public function getActiveList(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('id')
            ->get();
    }
}
