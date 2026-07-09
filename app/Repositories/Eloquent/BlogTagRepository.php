<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogTag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class BlogTagRepository extends BaseRepository
{
    protected function model(): string
    {
        return BlogTag::class;
    }

    /**
     * All tags ordered by name.
     */
    public function getAllOrdered(): Collection
    {
        return $this->query()->orderBy('name')->get();
    }

    /**
     * Names of tags attached to at least one published post.
     * Used for the blog post sidebar tag list.
     */
    public function getNamesWithPublishedPosts(): BaseCollection
    {
        return $this->query()
            ->whereHas('posts', fn ($q) => $q->published())
            ->pluck('name');
    }
}
