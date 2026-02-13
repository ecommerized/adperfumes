<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestMerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Merchant::create([
            'email' => 'test@merchant.com',
            'password' => Hash::make('password123'),
            'business_name' => 'Test Perfume Shop',
            'contact_name' => 'John Doe',
            'phone' => '+971501234567',
            'address' => '123 Business Street, Business Bay',
            'city' => 'Dubai',
            'country' => 'UAE',
            'trade_license' => 'TL-12345',
            'tax_registration' => 'TAX-67890',
            'status' => 'approved',
            'approved_at' => now(),
            'commission_percentage' => 15.00,
        ]);

        echo "✓ Test merchant created successfully!\n";
        echo "  Email: test@merchant.com\n";
        echo "  Password: password123\n";
        echo "  Status: approved\n";
    }
}
