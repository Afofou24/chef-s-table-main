<?php

namespace Database\Factories;

use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestaurantTableFactory extends Factory
{
    protected $model = RestaurantTable::class;

    public function definition(): array
    {
        return [
            'number' => $this->faker->unique()->numberBetween(1, 50),
            'capacity' => $this->faker->randomElement([2, 4, 6, 8]),
            'location' => $this->faker->randomElement(['Salle principale', 'Terrasse', 'Mezzanine']),
            'status' => 'available',
        ];
    }
}
