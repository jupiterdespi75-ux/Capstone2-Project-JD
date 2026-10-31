<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Gate::count() === 0) {
            foreach (['Gate 2', 'Gate 3', 'Gate 4'] as $name) {
                Gate::create(['name' => $name]);
            }
            $this->command->info('Seeded gates: Gate 2, Gate 3, Gate 4');
        }

        if (User::count() === 0) {
            User::create([
                'name' => 'System Admin',
                'email' => 'admin@qrode.uc.edu.ph',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]);
            User::create([
                'name' => 'Guard Juan',
                'email' => 'guard@qrode.uc.edu.ph',
                'password' => Hash::make('guard123'),
                'role' => 'guard',
            ]);
            $this->command->info('Seeded default accounts:');
            $this->command->info('  Admin -> admin@qrode.uc.edu.ph / admin123');
            $this->command->info('  Guard -> guard@qrode.uc.edu.ph / guard123');
        }
    }
}
