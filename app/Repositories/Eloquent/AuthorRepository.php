<?php

namespace App\Repositories\Eloquent;

use App\Models\Author;

class AuthorRepository extends BaseRepository
{
    protected function model(): string
    {
        return Author::class;
    }

    /**
     * Single active author by slug — 404s via ModelNotFoundException if missing/inactive.
     */
    public function findActiveBySlugOrFail(string $slug): Author
    {
        /** @var Author|null $author */
        $author = $this->query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $author) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(Author::class);
        }

        return $author;
    }
}
