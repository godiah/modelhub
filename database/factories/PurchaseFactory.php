<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'price_minor' => 50000,
            'currency' => 'KES',
            'status' => 'completed',
            'purchased_at' => now()->subDays(3),
        ];
    }
}
