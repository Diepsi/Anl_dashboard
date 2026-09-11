<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = env('ADMIN_PASSWORD') ?: Str::password(16);

        User::factory()->create([
            'name' => 'Admin ANL',
            'email' => 'admin@anl.com',
            'password' => $adminPassword,
            'role' => 'admin',
        ]);

        $this->command?->info('Akun admin dibuat: admin@anl.com / '.$adminPassword);

        $this->call([
            ShipmentSeeder::class,
        ]);
    }
}
