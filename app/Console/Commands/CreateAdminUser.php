<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin-user';

    protected $description = 'Create the admin user for Filament dashboard';

    public function handle(): int
    {
        $user = User::updateOrCreate(
            ['email' => 'info@adperfumes.ae'],
            [
                'name' => 'AD Perfumes',
                'password' => 'Adperfumes9630@@',
                'email_verified_at' => now(),
            ]
        );

        $this->info("Admin user created/updated: {$user->email}");

        return self::SUCCESS;
    }
}
