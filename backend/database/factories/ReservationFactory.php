<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'table_id' => RestaurantTable::factory(),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'guests_count' => $this->faker->numberBetween(1, 8),
            'reservation_datetime' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'notes' => $this->faker->sentence(),
        ];
    }
}
