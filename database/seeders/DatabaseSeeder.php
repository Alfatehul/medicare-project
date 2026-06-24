<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'whatsapp_number' => '081234567890',
        ]);

        $doctors = [
            [
                'name' => 'Dr. Andi Pratama',
                'specialization' => 'Spesialis Anak',
                'fee_idr' => 150000,
            ],
            [
                'name' => 'Dr. Budi Santoso',
                'specialization' => 'Spesialis Gigi',
                'fee_idr' => 200000,
            ],
            [
                'name' => 'Dr. Citra Dewi',
                'specialization' => 'Spesialis Kulit',
                'fee_idr' => 175000,
            ],
        ];

        foreach ($doctors as $doctor) {
            Doctor::create($doctor);
        }
    }
}
