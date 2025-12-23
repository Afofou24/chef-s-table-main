<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(1, true),
            'description' => $this->faker->sentence(),
            'sort_order' => $this->faker->unique()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
