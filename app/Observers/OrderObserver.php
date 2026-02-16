<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use App\Notifications\InvoiceGenerated;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderStatusChanged;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Notification;

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

        // Generate invoices immediately when order is placed
        $this->generateInvoicesForNewOrder($order);

        // Send notifications after order is fully created with items
        $this->sendNewOrderNotifications($order);
    }

    /**
     * Generate invoices for new order and send to customer
     */
    protected function generateInvoicesForNewOrder(Order $order): void
    {
        try {
            // Generate invoices (one per merchant)
            $invoices = app(InvoiceService::class)->generateInvoicesForOrder($order);

            // Generate PDF and send to customer for each invoice
            foreach ($invoices as $invoice) {
                // Generate PDF
                app(InvoiceService::class)->generateInvoicePdf($invoice);

                // Mark as sent
                $invoice->update(['status' => 'sent', 'sent_at' => now()]);

                // Send invoice to customer
                if ($order->customer) {
                    $order->customer->notify(new InvoiceGenerated($invoice));
                }
            }

            \Illuminate\Support\Facades\Log::info('Generated and sent ' . count($invoices) . ' invoice(s) for order #' . $order->order_number);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Invoice generation failed for order #' . $order->order_number . ': ' . $e->getMessage());
        }
    }

    /**
     * Send notifications for new order to admin and merchants
     */
    protected function sendNewOrderNotifications(Order $order): void
    {
        try {
            // Get all admin users
            $admins = User::all();

            // Send notification to all admins
            foreach ($admins as $admin) {
                $admin->notify(new NewOrderNotification($order));
            }

            // Group order items by merchant and notify each merchant
            $merchantTotals = [];
            foreach ($order->items as $item) {
                if ($item->merchant_id) {
                    if (!isset($merchantTotals[$item->merchant_id])) {
                        $merchantTotals[$item->merchant_id] = 0;
                    }
                    $merchantTotals[$item->merchant_id] += $item->total;
                }
            }

            // Notify each merchant
            foreach ($merchantTotals as $merchantId => $total) {
                $merchant = Merchant::find($merchantId);
                if ($merchant) {
                    $merchant->notify(new NewOrderNotification($order, $total));
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send new order notifications for order #' . $order->order_number . ': ' . $e->getMessage());
        }
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
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            // On delivery: set delivered_at, settlement eligibility
            if ($newStatus === 'delivered' && !$order->delivered_at) {
                $order->updateQuietly([
                    'delivered_at' => now(),
                    'settlement_eligible_at' => now()->addDays(15),
                    'is_refund_eligible' => true,
                ]);
            }

            // On cancellation: set cancelled_at
            if ($newStatus === 'cancelled' && !$order->cancelled_at) {
                $order->updateQuietly([
                    'cancelled_at' => now(),
                ]);
            }

            // Send status change notification to customer (only if old status exists)
            // Don't send on initial order creation
            if ($order->customer && $oldStatus !== null) {
                try {
                    $order->customer->notify(new OrderStatusChanged($order, $oldStatus, $newStatus));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send status change notification for order #' . $order->order_number . ': ' . $e->getMessage());
                }
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
