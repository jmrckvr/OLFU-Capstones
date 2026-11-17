<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** `php artisan db:seed` loads the demo data (same as `npm run seed:demo`, minus drawing images). */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
