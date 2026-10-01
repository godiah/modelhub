<?php

namespace Database\Factories;

use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SellerProfileFactory extends Factory
{
    protected $model = SellerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => SellerStatus::Pending,
            'display_name' => fake()->unique()->company(),
            'bio' => fake()->paragraph(4),
            'focus' => 'Architectural interiors and furniture, modelled in Blender.',
            'portfolio_url' => 'https://portfolio.example.test/'.fake()->unique()->slug(2),
            'terms_accepted_at' => now(),
            'submitted_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => SellerStatus::Approved, 'reviewed_at' => now()]);
    }

    public function rejected(string $notes = 'Please add more detail about your work.'): static
    {
        return $this->state(fn () => ['status' => SellerStatus::Rejected, 'reviewed_at' => now(), 'review_notes' => $notes]);
    }

    public function suspended(string $notes = 'Breach of the seller terms.'): static
    {
        return $this->state(fn () => ['status' => SellerStatus::Suspended, 'reviewed_at' => now(), 'review_notes' => $notes]);
    }
}
