<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['buffet', 'budget_meals', 'budget_snacks', 'snacks', 'drinks']),
            'price' => $this->faker->randomFloat(2, 5, 100),
            'image_path' => null, // This would typically be set when actually uploading an image
        ];
    }

    public function buffet(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'buffet',
            ];
        });
    }
    public function budget_meals(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'budget_meals',
            ];
        });
    }

    public function budget_snacks(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'budget_snacks',
            ];
        });
    }

    public function snacks(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'snacks',
            ];
        });
    }

    public function drinks(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'drinks',
            ];
        });
    }
} 