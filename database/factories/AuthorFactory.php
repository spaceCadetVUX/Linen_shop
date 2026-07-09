<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'user_id'   => null,
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 99999),
            'title'     => null,
            'bio'       => null,
            'avatar'    => null,
            'is_active' => true,
        ];
    }
}
