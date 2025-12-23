<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 5, 50),
            'preparation_time' => $this->faker->numberBetween(5, 30),
            'is_available' => true,
            'is_featured' => $this->faker->boolean(20),
            'calories' => $this->faker->numberBetween(100, 1000),
        ];
    }
}
