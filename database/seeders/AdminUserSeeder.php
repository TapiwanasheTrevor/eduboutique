<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('database.seeders.admin_user.email', env('ADMIN_USER_EMAIL', 'admin@eduboutique.co.zw'));
        $name = config('database.seeders.admin_user.name', env('ADMIN_USER_NAME', 'Admin User'));
        $plainPassword = config('database.seeders.admin_user.password', env('ADMIN_USER_PASSWORD', 'password'));

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($plainPassword),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        if (isset($this->command)) {
            $this->command->info("Admin user seeded:\n- Email: {$user->email}\n- Password: {$plainPassword}");
        }
    }
}
