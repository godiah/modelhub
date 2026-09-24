<?php

namespace Database\Factories;

use App\Models\ModelJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ModelJobFactory extends Factory
{
    protected $model = ModelJob::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 1000000),
            'description' => fake()->paragraph(),
            'skills' => [],
            'software' => [],
            'images' => null,
            'deadline' => null,
            'no_deadline' => true,
            'budget' => fake()->randomFloat(2, 50, 5000),
            'is_active' => true,
            'is_archived' => false,
            'applicants_count' => 0,
        ];
    }
}
