<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Models\BlogPostTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPostTranslation>
 */
class BlogPostTranslationFactory extends Factory
{
    protected $model = BlogPostTranslation::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(fake()->numberBetween(4, 8));

        return [
            'blog_post_id' => BlogPost::factory(),
            'locale'       => 'vi',
            'title'        => $title,
            'slug'         => Str::slug($title) . '-' . fake()->unique()->numberBetween(1000, 99999),
            'excerpt'      => fake()->paragraph(),
            'body'         => fake()->paragraphs(5, true),
        ];
    }
}
