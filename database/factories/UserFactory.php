<?php

namespace Database\Factories;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'nama' => fake()->name(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'peran' => Peran::Pegawai,
            'pegawai_id' => null,
            'aktif' => true,
            'wajib_ganti_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (): array => [
            'peran' => Peran::SuperAdmin,
        ]);
    }

    public function adminHr(): static
    {
        return $this->state(fn (): array => [
            'peran' => Peran::AdminHr,
        ]);
    }

    public function atasan(): static
    {
        return $this->state(fn (): array => [
            'peran' => Peran::Atasan,
        ]);
    }

    public function pegawai(): static
    {
        return $this->state(fn (): array => [
            'peran' => Peran::Pegawai,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (): array => [
            'aktif' => false,
        ]);
    }
}
