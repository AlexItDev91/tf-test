<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => SaleStatus::PAID,
            'total_cents' => $this->faker->numberBetween(100, 10000),
        ];
    }
}
