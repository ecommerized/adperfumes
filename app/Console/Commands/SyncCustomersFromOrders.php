<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Console\Command;

class SyncCustomersFromOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create customer records from existing orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = Order::orderBy('created_at')->get();
        $customersCreated = 0;

        $this->info('Syncing customers from orders...');
        $bar = $this->output->createProgressBar($orders->count());

        foreach ($orders->groupBy('email') as $email => $orderGroup) {
            $latestOrder = $orderGroup->sortByDesc('created_at')->first();

            $customer = Customer::firstOrCreate(
                ['email' => $email],
                [
                    'phone' => $latestOrder->phone,
                    'first_name' => $latestOrder->first_name,
                    'last_name' => $latestOrder->last_name,
                    'address' => $latestOrder->address,
                    'city' => $latestOrder->city,
                    'country' => $latestOrder->country,
                    'postal_code' => $latestOrder->postal_code,
                ]
            );

            // Update customer_id on all orders for this email
            Order::where('email', $email)->update(['customer_id' => $customer->id]);

            // Update customer stats
            $customer->updateStats();

            $customersCreated++;
            $bar->advance($orderGroup->count());
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Created/updated {$customersCreated} customers");

        return 0;
    }
}
