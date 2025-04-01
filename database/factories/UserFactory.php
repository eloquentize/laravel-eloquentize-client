<?php

namespace Database\Factories;

use App\Testing\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'active' => $this->faker->boolean,
        ];
    }
}
