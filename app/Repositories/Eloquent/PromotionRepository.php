<?php

namespace App\Repositories\Eloquent;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;

class PromotionRepository extends BaseRepository
{
    protected function model(): string
    {
        return Promotion::class;
    }

    public function activeOrdered(): Collection
    {
        return $this->query()
            ->active()
            ->orderBy('sort_order')
            ->orderByDesc('starts_at')
            ->get();
    }
}
