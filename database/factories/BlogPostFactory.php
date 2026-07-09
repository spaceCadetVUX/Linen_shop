<?php

namespace Database\Factories;

use App\Enums\BlogPostStatus;
use App\Models\Author;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogPost>
 *
 * blog_posts has no title/slug/excerpt/content of its own — those live on
 * blog_post_translations (see BlogPostTranslationFactory). Attach one
 * explicitly when the test needs to query/display the post by slug/title.
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        return [
            'author_id'        => Author::factory(),
            'blog_category_id' => BlogCategory::factory(),
            'featured_image'   => null,
            'status'           => BlogPostStatus::Published,
            'published_at'     => now()->subDays(fake()->numberBetween(1, 30)),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => BlogPostStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => BlogPostStatus::Draft,
            'published_at' => null,
        ]);
    }
}
