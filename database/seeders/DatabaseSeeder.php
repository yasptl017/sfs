<?php

namespace Database\Seeders;

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
        User::query()->updateOrCreate([
            'username' => 'admin',
        ], [
            'name' => 'Forest Division Admin',
            'email' => 'admin@sfs.local',
            'role' => 'division',
            'password' => 'admin',
        ]);
    }
}
