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
        if (User::exists()) {
            return;
        }

        // The password is generated here and only ever shown in this output
        $password = Str::password(16, symbols: false);

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => $password,
        ]);

        $this->command->warn("Superadmin created: {$user->email} / {$password}");
    }
}
