<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class CheckOrderData extends Command
{
    protected $signature = 'order:check {order_number}';
    protected $description = 'Check order data for AWB generation debugging';

    public function handle()
    {
        $orderNumber = $this->argument('order_number');

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            $this->error("Order {$orderNumber} not found");
            return 1;
        }

        $this->info("Order Data for: {$order->order_number}");
        $this->line("─────────────────────────────────────────────");

        $this->table(
            ['Field', 'Value', 'Status'],
            [
                ['ID', $order->id, '✓'],
                ['Order Number', $order->order_number, '✓'],
                ['First Name', $order->first_name ?? 'NULL', $order->first_name ? '✓' : '✗ MISSING'],
                ['Last Name', $order->last_name ?? 'NULL', $order->last_name ? '✓' : '✗ MISSING'],
                ['Full Name (accessor)', $order->full_name, strlen(trim($order->full_name)) > 0 ? '✓' : '✗ EMPTY'],
                ['Phone', $order->phone ?? 'NULL', $order->phone ? '✓' : '✗ MISSING'],
                ['Email', $order->email ?? 'NULL', $order->email ? '✓' : '⚠ OPTIONAL'],
                ['Address', $order->address ?? 'NULL', $order->address ? '✓' : '✗ MISSING'],
                ['City', $order->city ?? 'NULL', $order->city ? '✓' : '✗ MISSING'],
                ['Country', $order->country ?? 'NULL', $order->country ? '✓' : '⚠ WILL DEFAULT TO UAE'],
                ['Postal Code', $order->postal_code ?? 'NULL', $order->postal_code ? '✓' : '⚠ OPTIONAL'],
                ['Tracking Number', $order->tracking_number ?? 'NULL', $order->tracking_number ? '✓ ALREADY HAS AWB' : '⚠ NO AWB'],
            ]
        );

        $this->line("─────────────────────────────────────────────");

        // Check if ready for AWB
        $firstName = trim($order->first_name ?? '');
        $lastName = trim($order->last_name ?? '');
        $phone = trim($order->phone ?? '');
        $address = trim($order->address ?? '');
        $city = trim($order->city ?? '');

        $issues = [];
        if (empty($firstName)) $issues[] = 'Missing first name';
        if (empty($lastName)) $issues[] = 'Missing last name';
        if (empty($phone)) $issues[] = 'Missing phone number';
        if (empty($address)) $issues[] = 'Missing address';
        if (empty($city)) $issues[] = 'Missing city';

        if (empty($issues)) {
            $this->info("✓ This order is ready for AWB generation!");
            $this->line("\nShipment data that would be sent:");
            $this->line("  - Full Name: {$firstName} {$lastName}");
            $this->line("  - Phone: {$phone}");
            $this->line("  - Address: {$address}");
            $this->line("  - City: {$city}");
            $this->line("  - Country: " . ($order->country ?? 'UAE'));
        } else {
            $this->error("✗ This order is NOT ready for AWB generation:");
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            $this->line("\nPlease update the order with missing information before generating AWB.");
        }

        return 0;
    }
}
