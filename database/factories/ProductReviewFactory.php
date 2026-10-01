<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->sentences(2, true),
            'status' => 'visible',
        ];
    }

    public function hidden(string $reason = 'Breaks the review rules.'): static
    {
        return $this->state(fn () => ['status' => 'hidden', 'hidden_at' => now(), 'hidden_reason' => $reason]);
    }
}
