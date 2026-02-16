<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Customer;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderStatusChanged;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test {type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the notification system (types: new-order, status-change, all)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        $this->info('🔔 Testing Notification System');
        $this->newLine();

        // Get test data
        $admin = User::first();
        $merchant = Merchant::first();
        $customer = Customer::first();
        $order = Order::with('items')->latest()->first();

        if (!$admin) {
            $this->error('❌ No admin users found in database');
            return 1;
        }

        if (!$order) {
            $this->error('❌ No orders found in database');
            return 1;
        }

        $this->info("📊 Test Data:");
        $this->line("  - Admin: {$admin->email}");
        $this->line("  - Order: {$order->order_number}");
        if ($merchant) {
            $this->line("  - Merchant: {$merchant->business_name}");
        }
        if ($customer) {
            $this->line("  - Customer: {$customer->email}");
        }
        $this->newLine();

        // Test new order notification
        if ($type === 'new-order' || $type === 'all') {
            $this->info('Testing: New Order Notification');
            try {
                // Send to admin
                $admin->notify(new NewOrderNotification($order));
                $this->line("  ✅ Sent to admin: {$admin->email}");

                // Send to merchant if exists
                if ($merchant) {
                    $merchantTotal = $order->items->where('merchant_id', $merchant->id)->sum('total');
                    if ($merchantTotal > 0) {
                        $merchant->notify(new NewOrderNotification($order, $merchantTotal));
                        $this->line("  ✅ Sent to merchant: {$merchant->business_name} (AED {$merchantTotal})");
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
            }
            $this->newLine();
        }

        // Test status change notification
        if ($type === 'status-change' || $type === 'all') {
            $this->info('Testing: Order Status Change Notification');
            try {
                if ($customer) {
                    $customer->notify(new OrderStatusChanged($order, 'pending', 'confirmed'));
                    $this->line("  ✅ Sent to customer: {$customer->email}");
                } else {
                    $this->warn("  ⚠️  No customer found - creating test customer notification to order email");
                    if ($order->customer) {
                        $order->customer->notify(new OrderStatusChanged($order, 'pending', 'confirmed'));
                        $this->line("  ✅ Sent to order customer: {$order->customer->email}");
                    } else {
                        $this->warn("  ⚠️  Order has no customer linked");
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
            }
            $this->newLine();
        }

        // Check database
        $notificationCount = \DB::table('notifications')->count();
        $this->info("📬 Database Notifications:");
        $this->line("  Total notifications in database: {$notificationCount}");

        if ($notificationCount > 0) {
            $recent = \DB::table('notifications')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            $this->newLine();
            $this->info("  Recent notifications:");
            foreach ($recent as $notification) {
                $data = json_decode($notification->data, true);
                $message = $data['message'] ?? 'N/A';
                $readAt = $notification->read_at ? '(Read)' : '(Unread)';
                $this->line("    • {$message} {$readAt}");
            }
        }

        $this->newLine();
        $this->info('✅ Test completed!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Login to admin panel: ' . url('/admin'));
        $this->line('2. Check for the bell icon 🔔 in the top right');
        $this->line('3. You should see ' . $notificationCount . ' notification(s)');
        $this->newLine();

        return 0;
    }
}
