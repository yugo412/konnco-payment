<?php

namespace Database\Factories;

use App\Models\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 10_000, 10_000_000),
            'status' => fake()->randomElement(array_map(function (TransactionStatus $status): string {
                return $status->value;
            }, TransactionStatus::cases())),
        ];
    }
}
