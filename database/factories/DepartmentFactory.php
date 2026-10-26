<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->company();

        return [
            'kode' => Str::upper(Str::slug($nama, '_')),
            'nama' => $nama,
            'aktif' => true,
        ];
    }
}
