<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => fn () => Category::whereNotNull('parent_id')->inRandomOrder()->value('id'),
            'title' => fake()->unique()->words(3, true).' 3D model',
            'description' => fake()->paragraphs(2, true),
            'tags' => ['3d', 'model', fake()->word()],
            'status' => ProductStatus::Draft,
            'price_minor' => fake()->numberBetween(5, 500) * 10000,
            'currency' => 'KES',
            'geometry_type' => 'polygon_mesh',
            'polygons' => fake()->numberBetween(1000, 200000),
            'vertices' => fake()->numberBetween(1000, 200000),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Published, 'published_at' => now(), 'submitted_at' => now(), 'reviewed_at' => now()]);
    }

    public function inReview(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::InReview, 'submitted_at' => now()]);
    }

    public function free(): static
    {
        return $this->state(fn () => ['price_minor' => 0]);
    }
}
