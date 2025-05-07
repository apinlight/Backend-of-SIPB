<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'unique_id' => (string) Str::ulid(),
            'username' => $this->faker->userName,
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password', // Will be hashed by the model's mutator
            'branch_name' => $this->faker->company,
        ];
    }
    public function admin()
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }

    public function user()
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('user');
        });
    }
}
