<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        $offerAmount = fake()->randomFloat(2, 50, 5000);
        $serviceFee = $offerAmount * 0.10;

        return [
            'job_id' => ModelJob::factory(),
            'applicant_id' => User::factory(),
            'poster_id' => User::factory(),
            'offer_amount' => $offerAmount,
            'service_fee' => $serviceFee,
            'net_amount' => $offerAmount - $serviceFee,
            'proposal' => fake()->paragraph(),
            'portfolio' => [],
            'terms_accepted' => true,
            'status' => 'submitted',
            'is_archived' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function hired(): static
    {
        return $this->state(fn () => ['status' => 'hired']);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['is_archived' => true]);
    }
}
