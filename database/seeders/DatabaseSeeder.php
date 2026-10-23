<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Data contoh hanya boleh dibuat di lingkungan lokal, tidak pernah di produksi
        if (! app()->environment('local')) {
            $this->command?->warn('DatabaseSeeder dilewati: bukan lingkungan local.');

            return;
        }

        $this->call([
            CafeDemoSeeder::class,
            RecipeDemoSeeder::class,
        ]);
    }
}