<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\ShopifyService;
use Illuminate\Console\Command;

class ShopifyImportCustomers extends Command
{
    protected $signature = 'shopify:import-customers';
    protected $description = 'Import customers from Shopify store';

    protected $shopify;
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errorCount = 0;

    public function handle()
    {
        $this->shopify = new ShopifyService();

        $this->info('🚀 Starting Shopify customer import...');
        $this->newLine();

        // Fetch customers from Shopify
        $this->info('📦 Fetching customers from Shopify...');
        $shopifyCustomers = $this->shopify->getAllCustomers();

        $totalCustomers = count($shopifyCustomers);
        $this->info("✓ Found {$totalCustomers} customers");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalCustomers);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Starting...');

        foreach ($shopifyCustomers as $shopifyCustomer) {
            $customerName = ($shopifyCustomer['first_name'] ?? '') . ' ' . ($shopifyCustomer['last_name'] ?? '');
            $bar->setMessage("Importing: {$customerName}");

            try {
                $this->importCustomer($shopifyCustomer);
                $this->importedCount++;
            } catch (\Exception $e) {
                $this->errorCount++;
                $this->newLine();
                $this->error("✗ Error importing '{$customerName}': {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Import completed!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['✓ Imported', $this->importedCount],
                ['○ Skipped (duplicates)', $this->skippedCount],
                ['✗ Errors', $this->errorCount],
                ['Total', $totalCustomers],
            ]
        );

        return 0;
    }

    protected function importCustomer($shopifyCustomer)
    {
        // Check if customer already exists by email
        if (empty($shopifyCustomer['email'])) {
            $this->skippedCount++;
            return; // Skip customers without email
        }

        $existingCustomer = Customer::where('email', $shopifyCustomer['email'])->first();

        if ($existingCustomer) {
            $this->skippedCount++;
            return;
        }

        // Get default address
        $defaultAddress = $shopifyCustomer['default_address'] ?? [];

        // Create customer
        Customer::create([
            'email' => $shopifyCustomer['email'],
            'phone' => $shopifyCustomer['phone'] ?? $defaultAddress['phone'] ?? null,
            'first_name' => $shopifyCustomer['first_name'] ?? 'Unknown',
            'last_name' => $shopifyCustomer['last_name'] ?? '',
            'address' => $defaultAddress['address1'] ?? null,
            'city' => $defaultAddress['city'] ?? null,
            'country' => $defaultAddress['country'] ?? null,
            'postal_code' => $defaultAddress['zip'] ?? null,
            'marketing_email_opt_in' => $shopifyCustomer['accepts_marketing'] ?? false,
            'marketing_whatsapp_opt_in' => !empty($shopifyCustomer['phone']),
            'total_orders' => $shopifyCustomer['orders_count'] ?? 0,
            'total_spent' => floatval($shopifyCustomer['total_spent'] ?? 0),
            'first_order_at' => $shopifyCustomer['created_at'] ?? now(),
            'last_order_at' => $shopifyCustomer['updated_at'] ?? now(),
            'customer_segment' => $this->calculateSegment(
                $shopifyCustomer['orders_count'] ?? 0,
                floatval($shopifyCustomer['total_spent'] ?? 0)
            ),
        ]);
    }

    protected function calculateSegment($ordersCount, $totalSpent)
    {
        if ($ordersCount === 0) {
            return 'new';
        } elseif ($totalSpent >= 5000) {
            return 'vip';
        } elseif ($ordersCount > 1) {
            return 'regular';
        } else {
            return 'new';
        }
    }
}
