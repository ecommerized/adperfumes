<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\Order;
use App\Services\InvoiceService;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Find or create customer
        $customer = Customer::firstOrCreate(
            ['email' => $order->email],
            [
                'phone' => $order->phone,
                'first_name' => $order->first_name,
                'last_name' => $order->last_name,
                'address' => $order->address,
                'city' => $order->city,
                'country' => $order->country,
                'postal_code' => $order->postal_code,
            ]
        );

        // Link order to customer
        $order->update(['customer_id' => $customer->id]);

        // Update customer stats
        $customer->updateStats();
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->customer_id && $order->wasChanged('payment_status')) {
            $order->customer->updateStats();
        }

        // Auto-handle status changes
        if ($order->wasChanged('status')) {
            $newStatus = $order->status;

            // On delivery: set delivered_at, settlement eligibility, generate invoices
            if ($newStatus === 'delivered' && !$order->delivered_at) {
                $order->updateQuietly([
                    'delivered_at' => now(),
                    'settlement_eligible_at' => now()->addDays(15),
                    'is_refund_eligible' => true,
                ]);

                // Generate invoices (one per merchant)
                try {
                    app(InvoiceService::class)->generateInvoicesForOrder($order);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Invoice generation failed for order #' . $order->order_number . ': ' . $e->getMessage());
                }
            }

            // On cancellation: set cancelled_at
            if ($newStatus === 'cancelled' && !$order->cancelled_at) {
                $order->updateQuietly([
                    'cancelled_at' => now(),
                ]);
            }
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
